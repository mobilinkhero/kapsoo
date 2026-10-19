<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class AiService
{
    public function __construct(
        protected OpenAIService $openai,
        protected KnowledgeBaseService $kb,
        protected KapsoService $kapso,
    ) {}

    public function isEnabled(): bool
    {
        return $this->openai->isConfigured() && filter_var($this->setting('ai_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public function handleInbound(Message $message, bool $simulate = false): void
    {
        $conversation = $message->conversation()->with('contact', 'messages')->first();
        if (! $conversation || ! $this->isEnabled()) {
            return;
        }
        if ($conversation->ai_mode === 'off' || $conversation->ai_handoff) {
            // Still classify tags even when off? Classify tags only if enabled mode
            if ($conversation->ai_mode === 'off') {
                return;
            }
        }

        try {
            $result = $this->analyze($conversation, $message);
        } catch (\Throwable $e) {
            Log::error('AI analyze failed', ['error' => $e->getMessage()]);

            return;
        }

        // Persist tags/confidence/suggestion on the inbound message
        $message->update([
            'ai_tags' => $result['tags'] ?? [],
            'ai_confidence' => $result['confidence'] ?? null,
            'ai_suggested_reply' => $result['reply'] ?? null,
            'ai_handoff_triggered' => (bool) ($result['handoff'] ?? false),
        ]);

        // Update conversation aggregates
        $tags = array_values(array_unique(array_merge($conversation->ai_tags ?? [], $result['tags'] ?? [])));
        $conversation->update([
            'ai_tags' => $tags,
            'lead_stage' => $result['lead_stage'] ?? $conversation->lead_stage,
            'lead_score' => $result['lead_score'] ?? $conversation->lead_score,
        ]);

        // Handoff decision
        $handoff = (bool) ($result['handoff'] ?? false);
        $reason = $result['handoff_reason'] ?? null;
        // Keyword-based human request detection
        $body = strtolower($message->body ?? '');
        foreach (['human', 'agent', 'person', 'representative', 'speak to', 'talk to'] as $kw) {
            if (str_contains($body, $kw)) {
                $handoff = true;
                $reason = $reason ?: 'Customer requested a human agent';
                break;
            }
        }
        // Negative sentiment keywords
        foreach (['angry', 'frustrated', 'complaint', 'terrible', 'awful', 'useless', 'scam'] as $kw) {
            if (str_contains($body, $kw)) {
                $handoff = true;
                $reason = $reason ?: 'Negative sentiment detected';
                break;
            }
        }

        if ($handoff) {
            $conversation->update([
                'ai_handoff' => true,
                'handoff_reason' => $reason,
                'ai_mode' => 'off',
            ]);
            // In auto mode we still might have sent? We'll not auto-send if handoff
            // Notify by logging; UI shows handoff state
            Log::info('AI handoff triggered', ['conversation' => $conversation->id, 'reason' => $reason]);

            return;
        }

        // Confidence-based handoff
        $confidence = (float) ($result['confidence'] ?? 1);
        $threshold = (float) $this->setting('ai_handoff_confidence', '0.5');
        if ($confidence < $threshold) {
            $conversation->update([
                'ai_handoff' => true,
                'handoff_reason' => 'Low AI confidence ('.round($confidence, 2).')',
                'ai_mode' => 'off',
            ]);

            return;
        }

        // Auto mode: send reply
        if ($conversation->ai_mode === 'auto' && ! empty($result['reply'])) {
            if ($simulate) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'direction' => 'outbound',
                    'type' => 'text',
                    'body' => $result['reply'],
                    'status' => 'sent (simulated)',
                    'sent_at' => now(),
                ]);
            } else {
                $contact = $conversation->contact;
                $sendResult = $this->kapso->sendMessage($contact->phone, $result['reply']);
                $mid = $sendResult['messages'][0]['id'] ?? null;
                Message::create([
                    'conversation_id' => $conversation->id,
                    'kapso_message_id' => $mid,
                    'direction' => 'outbound',
                    'type' => 'text',
                    'body' => $result['reply'],
                    'status' => $mid ? 'sent' : 'failed',
                    'sent_at' => now(),
                ]);
            }
            $conversation->update(['last_message_at' => now()]);
        }
    }

    protected function analyze(Conversation $conversation, Message $message): array
    {
        $systemPrompt = $this->setting('ai_system_prompt', $this->defaultSystemPrompt());

        // RAG context
        $ragResult = $this->kb->search($message->body ?? '', 5, ['hyde' => false]);
        $ragChunks = $ragResult['chunks'] ?? [];
        $ragContext = empty($ragChunks) ? '' : "Relevant knowledge base:\n- ".implode("\n- ", $ragChunks);

        // Recent history (last 12 messages)
        $history = $conversation->messages()->orderByDesc('id')->limit(12)->get()->reverse();
        $chatHistory = [];
        foreach ($history as $m) {
            if ($m->id === $message->id) {
                continue;
            }
            $role = $m->direction === 'inbound' ? 'user' : 'assistant';
            if ($m->body) {
                $chatHistory[] = ['role' => $role, 'content' => $m->body];
            }
        }

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        if ($ragContext) {
            $messages[] = ['role' => 'system', 'content' => $ragContext];
        }
        $messages[] = ['role' => 'system', 'content' => 'Contact: '.($conversation->contact->name ?? $conversation->contact->phone).' (phone: '.$conversation->contact->phone.")\nCurrent AI tags: ".implode(', ', $conversation->ai_tags ?? [])."\nLead stage: ".($conversation->lead_stage ?? 'new')."\nAI mode: ".$conversation->ai_mode];
        foreach ($chatHistory as $h) {
            $messages[] = $h;
        }
        $messages[] = ['role' => 'user', 'content' => $message->body ?? '[non-text message]'];

        $instruction = "Respond ONLY with a valid JSON object (no markdown) with keys:\n"
            ."- reply: string, the suggested reply to send to the customer (concise, helpful, sales-oriented). Empty string if no reply needed.\n"
            ."- tags: array of short intent/sentiment tags (e.g. interested, pricing, complaint, hot_lead, support, greeting).\n"
            ."- confidence: number 0-1 indicating how confident you are in the reply.\n"
            ."- handoff: boolean, true if a human agent should take over.\n"
            ."- handoff_reason: string or null.\n"
            ."- lead_stage: one of new, qualified, negotiating, won, lost.\n"
            ."- lead_score: integer 0-100.\n";

        $messages[] = ['role' => 'system', 'content' => $instruction];

        $res = $this->openai->chat($messages, [
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (! $res['ok']) {
            throw new \RuntimeException($res['error'] ?? 'OpenAI error');
        }

        $parsed = json_decode($res['content'] ?? '{}', true);
        if (! is_array($parsed)) {
            $parsed = [];
        }

        return [
            'reply' => $parsed['reply'] ?? '',
            'tags' => array_values(array_filter((array) ($parsed['tags'] ?? []))),
            'confidence' => isset($parsed['confidence']) ? (float) $parsed['confidence'] : null,
            'handoff' => (bool) ($parsed['handoff'] ?? false),
            'handoff_reason' => $parsed['handoff_reason'] ?? null,
            'lead_stage' => $parsed['lead_stage'] ?? null,
            'lead_score' => isset($parsed['lead_score']) ? (int) $parsed['lead_score'] : null,
        ];
    }

    protected function setting(string $key, $default = null)
    {
        try {
            $value = Setting::where('key', $key)->value('value');

            return $value !== null ? $value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    protected function defaultSystemPrompt(): string
    {
        return 'You are an expert sales and support assistant for a WhatsApp business. '
            .'Be friendly, concise, and goal-oriented: qualify leads, answer questions, handle objections, and move conversations toward a purchase. '
            .'Use the knowledge base when relevant. If unsure or the customer is upset or asks for a human, set handoff=true. '
            .'Never make up facts not in the knowledge base.';
    }
}
