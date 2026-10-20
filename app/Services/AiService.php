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
        // Respect an explicitly disabled AI mode, but otherwise always reply.
        if ($conversation->ai_mode === 'off') {
            return;
        }

        $contact = $conversation->contact;

        // Transcribe voice notes / describe images so the AI can handle them
        if (empty($message->body) && ! empty($message->media_url)) {
            if (in_array($message->type, ['audio', 'voice', 'ptt'], true)) {
                $text = $this->openai->transcribe($message->media_url);
                if ($text) {
                    $message->update(['body' => $text]);
                    $message->refresh();
                }
            } elseif (in_array($message->type, ['image', 'sticker'], true)) {
                $desc = $this->openai->visionDescribe($message->media_url);
                if ($desc) {
                    $message->update(['body' => '[Image: '.$desc.']']);
                    $message->refresh();
                }
            }
        }

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
            'ai_sentiment' => $result['sentiment'] ?? null,
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
            ]);
            Log::info('AI handoff triggered', ['conversation' => $conversation->id, 'reason' => $reason]);
        }

        // Confidence-based handoff (flag only, keep replying)
        $confidence = (float) ($result['confidence'] ?? 1);
        $threshold = (float) $this->setting('ai_handoff_confidence', '0.5');
        if ($confidence < $threshold) {
            $conversation->update([
                'ai_handoff' => true,
                'handoff_reason' => 'Low AI confidence ('.round($confidence, 2).')',
            ]);
        }

        // Sentiment-based escalation: route to a human agent when sentiment is too low
        $sentiment = (float) ($result['sentiment'] ?? 1);
        $sentimentThreshold = (float) $this->setting('ai_escalation_sentiment', '0.3');
        if ($sentiment < $sentimentThreshold) {
            $escalationUserId = $this->setting('ai_escalation_user_id', null);
            $conversation->update([
                'ai_handoff' => true,
                'handoff_reason' => 'Negative sentiment ('.round($sentiment, 2).')',
                'assigned_to' => $escalationUserId ?: $conversation->assigned_to,
            ]);
        }

        // Always reply to every incoming message when AI is enabled.
        if (! empty($result['reply'])) {
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
                $sendContact = $conversation->contact;
                $sendResult = $this->kapso->sendMessage($sendContact->phone, $result['reply']);
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
        // Always enforce WhatsApp-friendly formatting regardless of custom prompt
        $systemPrompt .= "\n\nFORMATTING RULES (WhatsApp): "
            ."1) Write replies in multiple short lines using newlines, not one long paragraph. "
            ."2) Keep replies concise but complete: include all requested info when the customer asks, formatted one item per line. "
            ."3) ONLY on the very first message (no prior history): greet by name, then list EVERY available service (do not skip/summarize), each on its own line, then ask which service they need. "
            ."4) If there IS prior conversation history, do NOT repeat the greeting or the full service list. Answer the latest message directly and concisely, continuing the conversation. "
            ."5) When listing services, put each service on its own line with a bullet/emoji, never in a single paragraph. "
            ."6) Use emojis sparingly and only where genuinely helpful. Never in every line. "
            ."7) Ask as few questions as possible; only ask what is strictly needed. "
            ."8) Use clear spacing for details like payment info (each on its own line). "
            ."9) Do not repeat info already provided. "
            ."10) Prefer concise wording. Never re-send the full service list unless the customer asks for it again.";

        // RAG context
        $ragResult = $this->kb->search($message->body ?? '', 5, ['hyde' => false, 'expand' => false]);
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

        $isFirstMessage = $chatHistory === [];
        $instruction = "Respond ONLY with a valid JSON object (no markdown) with keys:\n"
            ."- reply: string, the suggested reply to send to the customer. Format for WhatsApp: use \\n for EACH line, emojis sparingly, clear spacing. ONLY on the first message (no prior history): greet by name, list EVERY available service (do not omit any), each on its own line, then ask which they need. If there is prior history, do NOT repeat the greeting or service list; answer the latest message directly and concisely.\n"
            ."EXAMPLE (follow this exact style, \\n between lines, single spacing):\n"
            ."Assalam-o-Alaikum Ahtisham! 😊\n"
            ."Here are our services:\n"
            ."👉 SIM Owner Details\n"
            ."👉 All Active Numbers on CNIC\n"
            ."👉 CDR Call & SMS History\n"
            ."👉 IMEI Tracking\n"
            ."👉 Family Tree\n"
            ."Which service do you need? 😊\n"
            ."- tags: array of short intent/sentiment tags (e.g. interested, pricing, complaint, hot_lead, support, greeting).\n"
            ."- confidence: number 0-1 indicating how confident you are in the reply.\n"
            ."- handoff: boolean, true if a human agent should take over.\n"
            ."- handoff_reason: string or null.\n"
            ."- lead_stage: one of new, qualified, negotiating, won, lost.\n"
            ."- lead_score: integer 0-100.\n"
            ."- sentiment: number 0-1 indicating customer sentiment (1=very positive, 0=very negative).\n";
        if ($isFirstMessage) {
            $instruction .= "This is the first message from this customer: greet them by name (if known) and start the conversation warmly.\n";
        }

        $messages[] = ['role' => 'system', 'content' => $instruction];

        $res = $this->openai->chat($messages, [
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (! $res['ok']) {
            throw new \RuntimeException($res['error'] ?? 'OpenAI error');
        }

        $parsed = json_decode($res['content'] ?? '{}', true);
        if (! is_array($parsed)) {
            $parsed = [];
        }

        $reply = trim((string) ($parsed['reply'] ?? ''));
        // Normalize: collapse 3+ newlines to 2, trim each line
        $reply = preg_replace("/\n{3,}/", "\n\n", $reply);
        $lines = array_map('trim', explode("\n", $reply));
        // Do not truncate: keep the full list the AI returns (no artificial cap)
        $reply = implode("\n", $lines);

        return [
            'reply' => $reply,
            'tags' => array_values(array_filter((array) ($parsed['tags'] ?? []))),
            'confidence' => isset($parsed['confidence']) ? (float) $parsed['confidence'] : null,
            'sentiment' => isset($parsed['sentiment']) ? (float) $parsed['sentiment'] : null,
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
        if (! empty($metadata['ai_profile'])) {
            $lines[] = 'Customer summary: '.$metadata['ai_profile'];
        }
        if (! empty($metadata['ai_tags'])) {
            $lines[] = 'Known tags: '.implode(', ', (array) $metadata['ai_tags']).'.';
        }

        $log = $metadata['ai_memory'] ?? [];
        if (is_array($log) && ! empty($log)) {
            $lines[] = 'Most recent exchanges (most recent last):';
            foreach (array_slice($log, -4) as $entry) {
                $in = trim((string) ($entry['inbound'] ?? ''));
                $out = trim((string) ($entry['reply'] ?? ''));
                if ($in === '' && $out === '') {
                    continue;
                }
                $lines[] = "- Customer: \"{$in}\" | AI: \"{$out}\"";
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

        // Periodically compress the raw log into a customer profile (every 3 exchanges)
        if (count($log) % 3 === 0 || count($log) === 1) {
            $profile = $this->openai->summarizeMemory($log);
            if ($profile) {
                $metadata['ai_profile'] = $profile;
            }
        }

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
            .'Remember the customer across messages: keep track of their name, needs, and prior answers, and do not ask for information already provided. '
            .'FORMATTING RULES (WhatsApp): '
            .'1) Write replies in multiple short lines using newlines (\\n), not one long paragraph. '
            .'2) Keep messages short (ideally under 6-8 lines). '
            .'3) Use emojis sparingly and only where they genuinely help (greetings, confirmations, payment). Do not put an emoji in every line. '
            .'4) Ask as few questions as possible; only ask what is strictly needed to move forward. '
            .'5) Use clear spacing between sections (e.g. payment details on separate lines). '
            .'6) Do not repeat information the customer already provided.';
    }
}
