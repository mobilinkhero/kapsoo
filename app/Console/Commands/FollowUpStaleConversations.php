<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Setting;
use App\Services\KapsoService;
use Illuminate\Console\Command;

class FollowUpStaleConversations extends Command
{
    protected $signature = 'ai:follow-up {--hours= : Hours of inactivity before following up}';

    protected $description = 'Send a follow-up nudge to conversations with no reply in N hours';

    public function handle(KapsoService $kapso): int
    {
        $setting = fn ($key, $default = null) => Setting::where('key', $key)->value('value') ?? $default;

        if (filter_var($setting('ai_followup_enabled', '1'), FILTER_VALIDATE_BOOLEAN) === false) {
            $this->info('Follow-ups disabled in settings.');

            return self::SUCCESS;
        }

        $hours = (int) ($this->option('hours') ?: $setting('ai_followup_hours', '24'));
        $hours = max(1, $hours);
        $cutoff = now()->subHours($hours);
        $template = $setting('ai_followup_template', '') ?: config('services.kapso.followup_template') ?: env('KAPSO_FOLLOWUP_TEMPLATE');
        $discount = (int) $setting('ai_followup_discount', '10');
        $messageTemplate = (string) $setting('ai_followup_message', "Hi {name}! 😊 Just checking in — you were interested in {service}. We'd love to help you get it done. As a special offer, enjoy {discount}% off your order today! Reply to claim your discount. 🎁");

        $conversations = Conversation::with('contact', 'messages')
            ->whereNotNull('last_inbound_at')
            ->where('last_inbound_at', '<=', $cutoff)
            ->where(function ($q) {
                $q->whereNull('followed_up_at')
                    ->orWhereColumn('followed_up_at', '<', 'last_inbound_at');
            })
            ->where('ai_mode', '!=', 'off')
            ->where('ai_handoff', false)
            ->get();

        $sent = 0;
        foreach ($conversations as $conv) {
            $contact = $conv->contact;
            if (! $contact || empty($contact->phone)) {
                continue;
            }

            // Derive the service the customer was interested in
            $service = $this->deriveService($conv);
            $price = $this->derivePrice($conv);

            $text = strtr($messageTemplate, [
                '{name}' => $contact->name ?: 'there',
                '{service}' => $service,
                '{discount}' => $discount,
                '{price}' => $price,
                '{phone}' => $contact->phone,
            ]);
            // Clean up empty price placeholder
            $text = preg_replace('/\s*\{price\}\s*/', $price ? ' '.$price.' ' : ' ', $text);

            if ($template) {
                $kapso->sendTemplate($contact->phone, $template);
            } else {
                $kapso->sendMessage($contact->phone, $text);
            }

            // Record the follow-up as an outbound message for history
            \App\Models\Message::create([
                'conversation_id' => $conv->id,
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $text,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $conv->followed_up_at = now();
            $conv->save();
            $sent++;
        }

        $this->info("Followed up {$sent} conversation(s).");

        return self::SUCCESS;
    }

    protected function deriveService(Conversation $conv): string
    {
        $tags = (array) ($conv->ai_tags ?? []);
        $generic = ['interested', 'pricing', 'hot_lead', 'support', 'greeting', 'complaint', 'payment', 'verified', 'handoff'];
        foreach ($tags as $t) {
            $t = (string) $t;
            if ($t !== '' && ! in_array(strtolower($t), $generic, true)) {
                return $t;
            }
        }

        // Fall back to the last inbound message body (truncated)
        $lastInbound = $conv->messages->where('direction', 'inbound')->last();
        if ($lastInbound && $lastInbound->body) {
            $body = trim(preg_replace('/\s+/', ' ', (string) $lastInbound->body));
            if (strlen($body) > 60) {
                $body = substr($body, 0, 60).'...';
            }
            if ($body !== '') {
                return $body;
            }
        }

        return 'your selected service';
    }

    protected function derivePrice(Conversation $conv): string
    {
        $amounts = [];
        try {
            $text = $conv->messages->where('direction', 'outbound')->pluck('body')->implode("\n");
            if (preg_match_all('/([\d][\d,]{2,9})\s*(?:pkr|rs\.?|rupees)/i', $text, $m)) {
                foreach ($m[1] as $v) {
                    $amounts[] = (float) str_replace(',', '', $v);
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        $amounts = array_values(array_unique($amounts));
        if (empty($amounts)) {
            return '';
        }
        // Smallest positive quoted amount is most likely the chosen service price
        sort($amounts);
        $price = (int) $amounts[0];

        return $price.' PKR';
    }
}