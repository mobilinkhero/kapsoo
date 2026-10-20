<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KapsoWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $event = $request->header('X-Webhook-Event');
        $payload = $request->all();

        Log::info('Kapso webhook received', [
            'event' => $event,
            'headers' => $request->headers->all(),
            'payload' => $payload,
        ]);

        // Handle batched deliveries
        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $item) {
                $this->processEvent($event, $item);
            }

            return response('OK', 200);
        }

        $this->processEvent($event, $payload);

        return response('OK', 200);
    }

    protected function processEvent(?string $event, array $data): void
    {
        try {
            match ($event) {
                'whatsapp.message.received' => $this->handleMessageReceived($data),
                'whatsapp.message.sent' => $this->handleMessageSent($data),
                'whatsapp.message.delivered' => $this->handleMessageStatus($data, 'delivered'),
                'whatsapp.message.read' => $this->handleMessageStatus($data, 'read'),
                'whatsapp.message.failed' => $this->handleMessageFailed($data),
                'whatsapp.conversation.created' => $this->handleConversationCreated($data),
                'whatsapp.conversation.ended' => $this->handleConversationEnded($data),
                'whatsapp.conversation.inactive' => $this->handleConversationInactive($data),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Kapso webhook processing error', [
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function upsertContact(array $data): Contact
    {
        $conv = $data['conversation'] ?? $data;
        $msg = $data['message'] ?? [];

        // Phone: try several locations Kapso/WhatsApp may use
        $phone = null;
        foreach (['phone_number', 'phone', 'wa_id', 'from'] as $k) {
            if (! empty($conv[$k])) {
                $phone = trim((string) $conv[$k]);
                break;
            }
        }
        if (! $phone && ! empty($msg['from'])) {
            $phone = trim((string) $msg['from']);
        }
        if (! $phone && ! empty($data['phone_number'])) {
            $phone = trim((string) $data['phone_number']);
        }

        // Name: Kapso/WhatsApp may send it in several places
        $name = null;
        foreach (['contact_name', 'name', 'profile_name'] as $k) {
            if (! empty($conv[$k])) {
                $name = trim((string) $conv[$k]);
                break;
            }
        }
        if (! $name) {
            // WhatsApp Cloud API style: contacts[0].profile.name
            $contacts = $data['contacts'] ?? ($conv['contacts'] ?? []);
            if (is_array($contacts) && ! empty($contacts[0])) {
                $c0 = $contacts[0];
                $name = $c0['profile']['name'] ?? ($c0['name'] ?? null);
                if ($name) {
                    $name = trim((string) $name);
                }
            }
        }
        if (! $name && ! empty($msg['contact']['profile']['name'])) {
            $name = trim((string) $msg['contact']['profile']['name']);
        }
        if (! $name && ! empty($msg['profile']['name'])) {
            $name = trim((string) $msg['profile']['name']);
        }

        $contact = $phone ? Contact::firstOrNew(['phone' => $phone]) : new Contact;
        // Only overwrite name when Kapso actually sends one (keep existing otherwise)
        if ($name) {
            $contact->name = $name;
        }
        if (isset($conv['business_scoped_user_id'])) {
            $contact->kapso_contact_id = $conv['business_scoped_user_id'];
        }
        if (isset($data['business_scoped_user_id'])) {
            $contact->kapso_contact_id = $data['business_scoped_user_id'];
        }
        $contact->last_seen_at = now();
        $contact->save();

        return $contact;
    }

    protected function upsertConversation(array $data, Contact $contact): Conversation
    {
        $conv = $data['conversation'] ?? [];

        // Reuse the most recent conversation for this contact so context/history is preserved
        // across messages instead of fragmenting into many threads.
        $conversation = Conversation::where('contact_id', $contact->id)
            ->where('status', '!=', 'ended')
            ->latest('last_message_at')
            ->first();
        if (! $conversation) {
            $conversation = Conversation::where('contact_id', $contact->id)
                ->latest('last_message_at')
                ->first();
        }
        if (! $conversation || $conversation->status === 'ended') {
            $conversation = new Conversation;
            $conversation->contact_id = $contact->id;
        }

        $conversation->phone_number_id = $data['phone_number_id'] ?? $conv['phone_number_id'] ?? $conversation->phone_number_id;
        $conversation->status = $conv['status'] ?? $conversation->status ?? 'active';
        if (empty($conversation->ai_mode)) {
            $conversation->ai_mode = 'auto';
        }
        $conversation->last_message_at = now();
        $conversation->save();

        return $conversation;
    }

    protected function handleMessageReceived(array $data): void
    {
        $convData = $data['conversation'] ?? [];
        $contact = $this->upsertContact($data);
        $conversation = $this->upsertConversation($data, $contact);

        $msg = $data['message'] ?? [];
        $kapso = $msg['kapso'] ?? [];

        $mediaUrl = $kapso['media_url'] ?? null;

        $body = $kapso['content'] ?? null;
        if (! $body && isset($msg['text']['body'])) {
            $body = $msg['text']['body'];
        }

        // Extract caption for media messages
        if (in_array($msg['type'] ?? '', ['image', 'video', 'document', 'audio', 'sticker'])) {
            $typeData = $kapso['message_type_data'] ?? [];
            if (! empty($typeData['caption'])) {
                $body = $typeData['caption'];
            } else {
                // Drop auto-generated attachment descriptions and raw URLs;
                // only a real caption should render as text
                if (is_string($body) && (str_contains($body, 'http') || str_contains(strtolower($body), 'attached'))) {
                    $body = null;
                } elseif (is_string($body) && preg_match('/^(image|video|audio|document|sticker|voice)\s+attached\b/i', $body)) {
                    $body = null;
                } elseif (empty($body)) {
                    $body = null;
                }
            }
        }

        // Location messages
        if (($msg['type'] ?? '') === 'location') {
            $loc = $msg['location'] ?? [];
            $body = trim(($loc['name'] ?? '').' '.($loc['address'] ?? ''));
            if (empty($body)) {
                $body = ($loc['latitude'] ?? '').', '.($loc['longitude'] ?? '');
            }
        }

        $kapsoMessageId = $msg['id'] ?? null;
        $messageData = [
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'type' => $msg['type'] ?? 'text',
            'body' => $body,
            'media_url' => $mediaUrl,
            'status' => 'received',
            'raw_payload' => $data,
            'sent_at' => isset($msg['timestamp']) ? Carbon::createFromTimestamp((int) $msg['timestamp']) : now(),
        ];

        if ($kapsoMessageId) {
            $saved = Message::updateOrCreate(
                ['kapso_message_id' => $kapsoMessageId],
                $messageData
            );
        } else {
            $saved = Message::create($messageData);
        }

        $conversation->increment('unread_count');
        $conversation->last_message_at = now();
        $conversation->last_inbound_at = now();
        $conversation->save();

        // Show a typing indicator while the AI generates its reply (2-5s)
        if ($saved && ! empty($saved->kapso_message_id)) {
            try {
                $kapso = app(KapsoService::class);
                $kapso->sendTypingIndicator($saved->kapso_message_id);
            } catch (\Throwable $e) {
                Log::warning('Typing indicator failed', ['error' => $e->getMessage()]);
            }
        }

        // Trigger AI processing for inbound messages (synchronously, no queue worker needed)
        if ($saved) {
            try {
                $ai = app(AiService::class);
                $ai->handleInbound($saved);
            } catch (\Throwable $e) {
                Log::error('AI processing failed in webhook', [
                    'message_id' => $saved->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function handleMessageSent(array $data): void
    {
        $convData = $data['conversation'] ?? [];
        $contact = $this->upsertContact($data);
        $conversation = $this->upsertConversation($data, $contact);

        $msg = $data['message'] ?? [];
        $kapso = $msg['kapso'] ?? [];

        $body = $kapso['content'] ?? null;
        if (! $body && isset($msg['text']['body'])) {
            $body = $msg['text']['body'];
        }
        $mediaUrl = $kapso['media_url'] ?? null;
        $type = $msg['type'] ?? 'text';

        if (in_array($type, ['image', 'video', 'document', 'audio', 'sticker'])) {
            if (is_string($body) && (filter_var($body, FILTER_VALIDATE_URL) || str_contains($body, 'http'))) {
                $body = null;
            }
        }

        $sentMessageData = [
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'type' => $type,
            'body' => $body,
            'status' => $kapso['status'] ?? 'sent',
            'raw_payload' => $data,
            'sent_at' => isset($msg['timestamp']) ? Carbon::createFromTimestamp((int) $msg['timestamp']) : now(),
        ];
        $sentKapsoId = $msg['id'] ?? null;
        if ($sentKapsoId) {
            Message::updateOrCreate(['kapso_message_id' => $sentKapsoId], $sentMessageData);
        } else {
            Message::create($sentMessageData);
        }
    }

    protected function handleMessageStatus(array $data, string $status): void
    {
        $msg = $data['message'] ?? [];
        if (! empty($msg['id'])) {
            Message::where('kapso_message_id', $msg['id'])->update(['status' => $status]);
        }
    }

    protected function handleMessageFailed(array $data): void
    {
        $msg = $data['message'] ?? [];
        if (! empty($msg['id'])) {
            Message::where('kapso_message_id', $msg['id'])->update(['status' => 'failed']);
        }
    }

    protected function handleConversationCreated(array $data): void
    {
        $convData = $data['conversation'] ?? [];
        $contact = $this->upsertContact($data);
        $this->upsertConversation($data, $contact);
    }

    protected function handleConversationEnded(array $data): void
    {
        $convData = $data['conversation'] ?? [];
        $contact = $this->upsertContact($data);
        $conversation = $this->upsertConversation($data, $contact);
        $conversation->status = 'ended';
        $conversation->save();
    }

    protected function handleConversationInactive(array $data): void
    {
        // Could trigger workflows; for now just log
        Log::info('Conversation inactive', $data);
    }
}
