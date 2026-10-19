<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\KapsoService;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $conversations = Conversation::with('contact', 'latestMessage')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('last_message_at')
            ->paginate(20);

        $selected = null;
        if ($request->filled('c')) {
            $selected = Conversation::with('contact', 'messages')->find($request->c);
        } elseif ($conversations->count()) {
            $selected = Conversation::with('contact', 'messages')->find($conversations->first()->id);
        }

        if ($selected) {
            $selected->update(['unread_count' => 0]);
        }

        return view('conversations.inbox', compact('conversations', 'selected'));
    }

    public function show(Conversation $conversation)
    {
        $conversation->load('contact', 'messages');
        $conversation->update(['unread_count' => 0]);

        return view('conversations.show', compact('conversation'));
    }

    public function sendMessage(Request $request, Conversation $conversation, KapsoService $kapso)
    {
        $data = $request->validate([
            'type' => 'required|string|in:text,image,video,audio,document,location,sticker',
            'body' => 'nullable|string|max:4096',
            'media_url' => 'nullable|url',
            'media_file' => 'nullable|file|max:16384',
            'caption' => 'nullable|string|max:1024',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_name' => 'nullable|string|max:255',
            'location_address' => 'nullable|string|max:500',
        ]);

        $contact = $conversation->contact;
        $type = $data['type'];

        // Handle file upload
        $mediaUrl = $data['media_url'] ?? null;
        if ($request->hasFile('media_file')) {
            $path = $request->file('media_file')->store('media', 'public');
            $mediaUrl = asset('storage/' . $path);
        }

        // Build Kapso payload based on type
        $payload = [];
        $body = $data['body'] ?? null;

        switch ($type) {
            case 'text':
                if (empty($body)) {
                    return redirect()->back()->with('error', 'Text body is required.');
                }
                $payload = ['text' => ['body' => $body]];
                break;
            case 'image':
            case 'video':
            case 'audio':
            case 'document':
            case 'sticker':
                if (empty($mediaUrl)) {
                    return redirect()->back()->with('error', 'Media URL is required.');
                }
                $media = ['link' => $mediaUrl];
                if (!empty($data['caption'])) {
                    $media['caption'] = $data['caption'];
                }
                if ($type === 'document' && !empty($data['body'])) {
                    $media['filename'] = $data['body'];
                }
                $payload = [$type => $media];
                $body = $data['caption'] ?? $data['body'] ?? null;
                break;
            case 'location':
                if (empty($data['latitude']) || empty($data['longitude'])) {
                    return redirect()->back()->with('error', 'Latitude and longitude are required.');
                }
                $loc = [
                    'latitude' => (string) $data['latitude'],
                    'longitude' => (string) $data['longitude'],
                ];
                if (!empty($data['location_name'])) $loc['name'] = $data['location_name'];
                if (!empty($data['location_address'])) $loc['address'] = $data['location_address'];
                $payload = ['location' => $loc];
                $body = $data['location_name'] ?? 'Location';
                break;
        }

        try {
            $result = $kapso->send($contact->phone, $type, $payload);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Kapso send exception', ['error' => $e->getMessage()]);
            return redirect()->route('conversations.show', $conversation)->with('error', 'Failed to send: ' . $e->getMessage());
        }

        $messageId = $result['messages'][0]['id'] ?? null;

        if (!$messageId) {
            $error = $result['error']['message'] ?? $result['error'] ?? json_encode($result);
            \Illuminate\Support\Facades\Log::error('Kapso send failed', ['response' => $result, 'to' => $contact->phone]);
            return redirect()->route('conversations.show', $conversation)->with('error', 'Kapso rejected the message: ' . (is_string($error) ? $error : json_encode($error)));
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'kapso_message_id' => $messageId,
            'direction' => 'outbound',
            'type' => $type,
            'body' => $body,
            'media_url' => $mediaUrl,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return redirect()->route('conversations.show', $conversation)->with('success', 'Message sent.');
    }

    public function markRead(Conversation $conversation)
    {
        $conversation->update(['unread_count' => 0]);
        return response()->json(['ok' => true]);
    }

    public function poll(Conversation $conversation, Request $request)
    {
        $afterId = (int) $request->get('after_id', 0);
        $messages = $conversation->messages()
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'direction' => $m->direction,
                'type' => $m->type,
                'body' => $m->body,
                'media_url' => $m->media_url,
                'status' => $m->status,
                'time' => $m->sent_at?->format('H:i'),
            ]);

        return response()->json([
            'messages' => $messages,
            'unread_count' => $conversation->fresh()->unread_count,
        ]);
    }
}