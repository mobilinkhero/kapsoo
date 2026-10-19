<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Services\KapsoService;
use Illuminate\Console\Command;

class FollowUpStaleConversations extends Command
{
    protected $signature = 'ai:follow-up {--hours=24 : Hours of inactivity before following up}';

    protected $description = 'Send a follow-up template to conversations with no reply in N hours';

    public function handle(KapsoService $kapso): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);
        $template = config('services.kapso.followup_template') ?: env('KAPSO_FOLLOWUP_TEMPLATE');

        $conversations = Conversation::with('contact')
            ->whereNotNull('last_inbound_at')
            ->where('last_inbound_at', '<=', $cutoff)
            ->where(function ($q) {
                $q->whereNull('followed_up_at')
                    ->orWhereColumn('followed_up_at', '<', 'last_inbound_at');
            })
            ->where('ai_mode', '!=', 'off')
            ->get();

        $sent = 0;
        foreach ($conversations as $conv) {
            $contact = $conv->contact;
            if (! $contact || empty($contact->phone)) {
                continue;
            }
            if ($template) {
                $kapso->sendTemplate($contact->phone, $template);
            } else {
                $kapso->sendMessage($contact->phone, 'Hi! Just checking in — is there anything else I can help you with?');
            }
            $conv->followed_up_at = now();
            $conv->save();
            $sent++;
        }

        $this->info("Followed up {$sent} conversation(s).");

        return self::SUCCESS;
    }
}
