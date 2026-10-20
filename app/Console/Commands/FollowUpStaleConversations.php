<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Setting;
use App\Services\KapsoService;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

class FollowUpStaleConversations extends Command
{
    protected $signature = 'ai:follow-up {--minutes= : Minutes of inactivity before following up} {--hours= : Hours (legacy, converted to minutes)}';

    protected $description = 'Send an AI-generated follow-up nudge to stale conversations';

    public function handle(KapsoService $kapso, OpenAIService $openai): int
    {
        $setting = fn ($key, $default = null) => Setting::where('key', $key)->value('value') ?? $default;

        \Illuminate\Support\Facades\Log::info('ai:follow-up started', ['minutes_option' => $this->option('minutes')]);

        if (filter_var($setting('ai_followup_enabled', '1'), FILTER_VALIDATE_BOOLEAN) === false) {
            $this->info('Follow-ups disabled in settings.');

            return self::SUCCESS;
        }

        $minutesOption = $this->option('minutes');
        $hoursOption = $this->option('hours');
        if ($minutesOption !== null && $minutesOption !== '') {
            $minutes = (int) $minutesOption;
        } elseif ($hoursOption !== null && $hoursOption !== '') {
            $minutes = (int) $hoursOption * 60;
        } else {
            $minutesSetting = $setting('ai_followup_minutes', null);
            if ($minutesSetting !== null && $minutesSetting !== '') {
                $minutes = (int) $minutesSetting;
            } else {
                $minutes = (int) $setting('ai_followup_hours', '24') * 60;
            }
        }
        $minutes = max(1, min(1440, $minutes));
        $cutoff = now()->subMinutes($minutes);
        $discount = (int) $setting('ai_followup_discount', '10');
        $extra = trim((string) $setting('ai_followup_extra', ''));

        $conversations = Conversation::with('contact', 'messages')
            ->whereNotNull('last_inbound_at')
            ->where('last_inbound_at', '<=', $cutoff)
            ->where(function ($q) {
                $q->whereNull('followed_up_at')
                    ->orWhereColumn('followed_up_at', '<', 'last_inbound_at');
            })
            ->where(function ($q) use ($minutes) {
                // Cooldown: never follow up again within the configured delay
                // after the last follow-up (prevents repeat sends / spam).
                $q->whereNull('followed_up_at')
                    ->orWhere('followed_up_at', '<=', now()->subMinutes($minutes));
            })
            ->where('ai_mode', '!=', 'off')
            ->get();

        $sent = 0;
        foreach ($conversations as $conv) {
            $contact = $conv->contact;
            if (! $contact || empty($contact->phone)) {
                continue;
            }

            if (! $openai->isConfigured()) {
                $this->warn('OpenAI not configured; skipping.');

                continue;
            }

            // Build recent conversation transcript for the AI
            $history = $conv->messages()->orderBy('id')->limit(30)->get();
            $transcript = [];
            foreach ($history as $m) {
                if (empty($m->body)) {
                    continue;
                }
                $transcript[] = ($m->direction === 'inbound' ? 'Customer' : 'Assistant').': '.$m->body;
            }
            $transcriptText = implode("\n", $transcript);

            $system = 'You are a friendly WhatsApp sales assistant writing in simple Roman Urdu (Urdu written in English letters), like a real Pakistani salesperson. '
                .'The customer has been inactive. Write ONE short follow-up message (4-6 lines) that converts them to buy: '
                .'(1) warm greeting with their name, '
                .'(2) remind them of exactly what they asked for and that it is ready/pending, '
                .'(3) create gentle urgency (limited time / slots), '
                .'(4) offer a '.$discount.'% discount as a real incentive to act now, '
                .'(5) end with ONE clear call-to-action asking them to reply "YES" or send payment. '
                .'Use simple everyday Roman Urdu words (e.g. "Assalam o Alaikum", "aap", "kaam", "tayar", "discount", "jaldi"). '
                .'EXAMPLE (follow this warm, converting style):\n'
                ."Assalam o Alaikum Ahtisham! 😊\n"
                ."Aapka SIM Owner Details ka kaam ready hai, bas payment pending hai.\n"
                ."Aaj ke liye special offer: 10% discount sirf aaj ke liye!\n"
                ."Kya aap abhi payment kar dein? Reply 'YES' aur main details bhej deta hoon. 🚀\n"
                .'Do NOT repeat the full service list. Do NOT make up facts. Do NOT use English paragraphs. '
                .'Respond with ONLY the message text, no JSON, no markdown.';
            if ($extra !== '') {
                $system .= ' Additional instruction: '.$extra;
            }

            $messages = [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Customer name: ".($contact->name ?: 'unknown')."\nDiscount to offer: ".$discount."%\n\nConversation so far:\n".$transcriptText."\n\nWrite a converting Roman Urdu follow-up message now."],
            ];

            $res = $openai->chat($messages, ['temperature' => 0.6, 'max_tokens' => 200]);
            if (! ($res['ok'] ?? false) || empty($res['content'])) {
                $this->warn("AI failed for conversation {$conv->id}; skipping.");

                continue;
            }

            $text = trim((string) $res['content']);
            // Normalize newlines
            $text = preg_replace("/\n{3,}/", "\n\n", $text);

            $kapso->sendMessage($contact->phone, $text);

            Message::create([
                'conversation_id' => $conv->id,
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $text,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $conv->followed_up_at = now();
            $conv->update(['last_message_at' => now()]);
            $conv->save();
            $sent++;
        }

        $this->info("Followed up {$sent} conversation(s).");
        \Illuminate\Support\Facades\Log::info('ai:follow-up finished', ['sent' => $sent]);

        return self::SUCCESS;
    }
}