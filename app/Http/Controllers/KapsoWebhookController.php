<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
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

    protected function upsertContact(array $conversation): Contact
    {
        $phone = $conversation['phone_number'] ?? null;
        $name = $conversation['contact_name'] ?? null;

        $contact = $phone ? Contact::firstOrNew(['phone' => $phone]) : new Contact;
        if ($name) {
            $contact->name = $name;
        }
        if (isset($conversation['business_scoped_user_id'])) {
            $contact->kapso_contact_id = $conversation['business_scoped_user_id'];
        }
        $contact->last_seen_at = now();
        $contact->save();

        return $contact;
    }

    protected function upsertConversation(array $data, Contact $contact): Conversation
    {
        $conv = $data['conversation'] ?? [];
        $kapsoConvId = $conv['id'] ?? null;

        $conversation = $kapsoConvId
            ? Conversation::firstOrNew(['id' => null]) // we use auto id; store kapso id in? We'll just find by contact
            : null;

        // Simpler: find by contact_id
        $conversation = Conversation::where('contact_id', $contact->id)->latest()->first();
        if (! $conversation) {
            $conversation = new Conversation;
            $conversation->contact_id = $contact->id;
        }

        $conversation->phone_number_id = $data['phone_number_id'] ?? $conv['phone_number_id'] ?? $conversation->phone_number_id;
        $conversation->status = $conv['status'] ?? $conversation->status ?? 'active';
        $conversation->last_message_at = now();
        $conversation->save();

        return $conversation;
    }

    protected function handleMessageReceived(array $data): void
    {
        $convData = $data['conversation'] ?? [];
        $contact = $this->upsertContact($convData);
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
                // Never store a raw media URL as the message body/caption
                if (is_string($body) && (filter_var($body, FILTER_VALIDATE_URL) || $body === $mediaUrl)) {
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

        Message::updateOrCreate(
            ['kapso_message_id' => $msg['id'] ?? null],
            [
                'conversation_id' => $conversation->id,
                'direction' => 'inbound',
                'type' => $msg['type'] ?? 'text',
                'body' => $body,
                'media_url' => $mediaUrl,
                'status' => 'received',
                'raw_payload' => $data,
                'sent_at' => isset($msg['timestamp']) ? Carbon::createFromTimestamp((int) $msg['timestamp']) : now(),
            ]
        );

        $conversation->increment('unread_count');
        $conversation->last_message_at = now();
        $conversation->save();
    }

    protected function handleMessageSent(array $data): void
    {
        $convData = $data['conversation'] ?? [];
        $contact = $this->upsertContact($convData);
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
            if (is_string($body) && (filter_var($body, FILTER_VALIDATE_URL) || $body === $mediaUrl)) {
                $body = null;
            }
        }

        Message::updateOrCreate(
            ['kapso_message_id' => $msg['id'] ?? null],
            [
                'conversation_id' => $conversation->id,
                'direction' => 'outbound',
                'type' => $type,
                'body' => $body,
                'status' => $kapso['status'] ?? 'sent',
                'raw_payload' => $data,
                'sent_at' => isset($msg['timestamp']) ? Carbon::createFromTimestamp((int) $msg['timestamp']) : now(),
            ]
        );
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
        $contact = $this->upsertContact($convData);
        $this->upsertConversation($data, $contact);
    }

    protected function handleConversationEnded(array $data): void
    {
        $convData = $data['conversation'] ?? [];
        $contact = $this->upsertContact($convData);
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
