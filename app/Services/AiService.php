<?php

namespace App\Services;

use App\Models\Contact;
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
            if ($conversation->ai_mode === 'off') {
                return;
            }
        }

        $contact = $conversation->contact;

        try {
            $result = $this->analyze($conversation, $message, $contact);
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
            Log::info('AI handoff triggered', ['conversation' => $conversation->id, 'reason' => $reason]);

            $this->updateContactMemory($contact, $conversation, $message, $result);

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

            $this->updateContactMemory($contact, $conversation, $message, $result);

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

        $this->updateContactMemory($contact, $conversation, $message, $result);
    }

    protected function analyze(Conversation $conversation, Message $message, ?Contact $contact = null): array
    {
        $systemPrompt = $this->setting('ai_system_prompt', $this->defaultSystemPrompt());

        // RAG context
        $ragResult = $this->kb->search($message->body ?? '', 5, ['hyde' => false]);
        $ragChunks = $ragResult['chunks'] ?? [];
        $ragContext = empty($ragChunks) ? '' : "Relevant knowledge base:\n- ".implode("\n- ", $ragChunks);

        // Recent history (last 20 messages of this conversation)
        $history = $conversation->messages()->orderByDesc('id')->limit(20)->get()->reverse();
        $chatHistory = [];
        $historyArr = $history->values();
        foreach ($historyArr as $idx => $m) {
            if ($m->id === $message->id) {
                continue;
            }
            if ($m->body) {
                $chatHistory[] = ['role' => $m->direction === 'inbound' ? 'user' : 'assistant', 'content' => $m->body];
            }
            // In assist mode the AI suggestion is stored on the inbound message but no
            // outbound row exists; surface it as an assistant turn so the AI remembers
            // what it previously suggested to this customer. Skip if a following outbound
            // message already carries the same text.
            if ($m->direction === 'inbound' && ! empty($m->ai_suggested_reply)) {
                $next = $historyArr->get($idx + 1);
                $alreadyRecorded = $next && $next->direction === 'outbound' && $next->body === $m->ai_suggested_reply;
                if (! $alreadyRecorded) {
                    $chatHistory[] = ['role' => 'assistant', 'content' => $m->ai_suggested_reply];
                }
            }
        }

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        // Customer identity + persistent memory
        $contactName = $contact?->name ?: ($contact?->phone ?? 'Unknown');
        $contactPhone = $contact?->phone ?? '';
        $identityBlock = "You are speaking with customer: {$contactName} (phone: {$contactPhone}). "
            .'Remember details about this customer from previous interactions and address them by name when natural. '
            .'Do not confuse this customer with others.';
        $messages[] = ['role' => 'system', 'content' => $identityBlock];

        // Persistent per-customer memory (across conversations)
        $memoryBlock = $this->buildContactMemoryBlock($contact);
        if ($memoryBlock !== '') {
            $messages[] = ['role' => 'system', 'content' => $memoryBlock];
        }

        if ($ragContext) {
            $messages[] = ['role' => 'system', 'content' => $ragContext];
        }
        $messages[] = ['role' => 'system', 'content' => 'Conversation context: '
            .'Current AI tags: '.implode(', ', $conversation->ai_tags ?? [])."\n"
            .'Lead stage: '.($conversation->lead_stage ?? 'new')."\n"
            .'Lead score: '.($conversation->lead_score ?? 0)."\n"
            .'AI mode: '.$conversation->ai_mode];
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

    protected function buildContactMemoryBlock(?Contact $contact): string
    {
        if (! $contact) {
            return '';
        }
        $metadata = is_array($contact->metadata) ? $contact->metadata : [];
        $lines = [];

        $name = $contact->name ?: $contact->phone;
        $lines[] = "Customer profile: {$name} ({$contact->phone}).";

        if (! empty($metadata['lead_stage'])) {
            $lines[] = "Known lead stage: {$metadata['lead_stage']} (score: ".($metadata['lead_score'] ?? 'n/a').').';
        }
        if (! empty($metadata['ai_tags'])) {
            $lines[] = 'Known tags: '.implode(', ', (array) $metadata['ai_tags']).'.';
        }

        $log = $metadata['ai_memory'] ?? [];
        if (is_array($log) && ! empty($log)) {
            $lines[] = 'Previous interactions with this customer (most recent last):';
            foreach (array_slice($log, -6) as $entry) {
                $in = trim((string) ($entry['inbound'] ?? ''));
                $out = trim((string) ($entry['reply'] ?? ''));
                $t = implode(', ', (array) ($entry['tags'] ?? []));
                if ($in === '' && $out === '') {
                    continue;
                }
                $lines[] = "- Customer said: \"{$in}\" | AI replied: \"{$out}\" | tags: {$t}";
            }
        }

        if (count($lines) <= 1) {
            return '';
        }

        return "Persistent memory for this customer (use it to stay consistent and not repeat questions):\n".implode("\n", $lines);
    }

    protected function updateContactMemory(Contact $contact, Conversation $conversation, Message $message, array $result): void
    {
        if (! $contact) {
            return;
        }
        $metadata = is_array($contact->metadata) ? $contact->metadata : [];

        $log = $metadata['ai_memory'] ?? [];
        if (! is_array($log)) {
            $log = [];
        }
        $log[] = [
            'at' => now()->toDateTimeString(),
            'inbound' => mb_substr((string) ($message->body ?? ''), 0, 300),
            'reply' => mb_substr((string) ($result['reply'] ?? ''), 0, 300),
            'tags' => array_values((array) ($result['tags'] ?? [])),
            'lead_stage' => $result['lead_stage'] ?? $conversation->lead_stage,
            'lead_score' => $result['lead_score'] ?? $conversation->lead_score,
        ];
        // Keep last 12 entries
        $log = array_slice($log, -12);

        $metadata['ai_memory'] = $log;
        $metadata['lead_stage'] = $result['lead_stage'] ?? ($metadata['lead_stage'] ?? $conversation->lead_stage);
        $metadata['lead_score'] = $result['lead_score'] ?? ($metadata['lead_score'] ?? $conversation->lead_score);
        $metadata['ai_tags'] = array_values(array_unique(array_merge(
            (array) ($metadata['ai_tags'] ?? []),
            (array) ($result['tags'] ?? [])
        )));

        $contact->metadata = $metadata;
        try {
            $contact->save();
        } catch (\Throwable $e) {
            Log::warning('Failed to update contact memory', ['error' => $e->getMessage()]);
        }
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
            .'Never make up facts not in the knowledge base. '
            .'Remember the customer across messages: keep track of their name, needs, and prior answers, and do not ask for information already provided.';
    }
}
