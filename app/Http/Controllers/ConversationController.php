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

        return view('conversations.index', compact('conversations'));
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
            'body' => 'required|string|max:4096',
        ]);

        $contact = $conversation->contact;

        try {
            $result = $kapso->sendMessage($contact->phone, $data['body']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Kapso sendMessage exception', ['error' => $e->getMessage()]);
            return redirect()->route('conversations.show', $conversation)->with('error', 'Failed to send: ' . $e->getMessage());
        }

        $messageId = $result['messages'][0]['id'] ?? null;

        if (!$messageId) {
            $error = $result['error']['message'] ?? $result['error'] ?? json_encode($result);
            \Illuminate\Support\Facades\Log::error('Kapso sendMessage failed', ['response' => $result, 'to' => $contact->phone]);
            return redirect()->route('conversations.show', $conversation)->with('error', 'Kapso rejected the message: ' . (is_string($error) ? $error : json_encode($error)));
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'kapso_message_id' => $messageId,
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $data['body'],
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
}