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
                // Send follow-up only ONCE per conversation, ever.
                $q->whereNull('followed_up_at');
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

            // Re-fetch fresh to avoid acting on stale data from a concurrent run
            $conv = Conversation::find($conv->id);
            if (! $conv || $conv->followed_up_at !== null) {
                continue; // already followed up (possibly by a concurrent run)
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

            // Derive the quoted price (if any) so we can state the discount in PKR
            $priceNum = 0;
            try {
                $allOut = $conv->messages->where('direction', 'outbound')->pluck('body')->implode("\n");
                if (preg_match_all('/([\d][\d,]{2,9})\s*(?:pkr|rs\.?|rupees)/i', $allOut, $m)) {
                    $amounts = array_map(fn ($v) => (float) str_replace(',', '', $v), $m[1]);
                    sort($amounts);
                    if (! empty($amounts)) {
                        $priceNum = (int) $amounts[0];
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
            $discountAmount = $priceNum > 0 ? (int) round($priceNum * $discount / 100) : 0;
            $finalPrice = $priceNum > 0 ? ($priceNum - $discountAmount) : 0;

            $priceLine = $priceNum > 0
                ? "Original price: {$priceNum} PKR. Discount: {$discount}% = {$discountAmount} PKR off. Final price after discount: {$finalPrice} PKR."
                : "No specific price was quoted; offer a {$discount}% discount.";

            $system = 'You are a WhatsApp sales assistant. Write ONE very short follow-up message (2-3 lines max) in simple Roman Urdu. '
                .'The customer was interested in a service but went silent. Message must: '
                .'(1) greet by name, '
                .'(2) ask if they still need that service, '
                .'(3) mention ONLY the discounted price in PKR (e.g. "315 PKR" instead of "350 PKR"), do NOT mention any percentage, '
                .'(4) end with a short question inviting them to reply. '
                .'Keep it under 3 lines. No urgency hype. No long paragraphs. No service lists. No extra fluff. '
                .'Respond with ONLY the message text, no JSON, no markdown.';
            if ($extra !== '') {
                $system .= ' Additional instruction: '.$extra;
            }

            $messages = [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Customer name: ".($contact->name ?: 'unknown')."\nService they wanted: see conversation below.\nDiscounted price to mention: {$finalPrice} PKR (original {$priceNum} PKR, {$discountAmount} PKR off). Do NOT mention percentage.\n\nConversation so far:\n".$transcriptText."\n\nWrite a very short follow-up (2-3 lines). Ask if they still need the service. Mention only the discounted price in PKR. Nothing else."],
            ];

            $res = $openai->chat($messages, ['temperature' => 0.6, 'max_tokens' => 200]);
            if (! ($res['ok'] ?? false) || empty($res['content'])) {
                $this->warn("AI failed for conversation {$conv->id}; skipping.");

                continue;
            }

            $text = trim((string) $res['content']);
            // Normalize newlines
            $text = preg_replace("/\n{3,}/", "\n\n", $text);

            // Mark as followed up BEFORE sending, so concurrent runs skip this conversation
            $conv->followed_up_at = now();
            $conv->update(['last_message_at' => now()]);
            $conv->save();

            $kapso->sendMessage($contact->phone, $text);

            Message::create([
                'conversation_id' => $conv->id,
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $text,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $sent++;
        }

        $this->info("Followed up {$sent} conversation(s).");
        \Illuminate\Support\Facades\Log::info('ai:follow-up finished', ['sent' => $sent]);

        return self::SUCCESS;
    }
}