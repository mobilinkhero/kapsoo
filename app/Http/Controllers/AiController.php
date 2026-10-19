<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\AiService;
use App\Services\KapsoService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(protected AiService $ai, protected KapsoService $kapso) {}

    public function setMode(Request $request, Conversation $conversation)
    {
        $data = $request->validate([
            'mode' => 'required|string|in:off,assist,auto',
        ]);
        $conversation->update(['ai_mode' => $data['mode'], 'ai_handoff' => false, 'handoff_reason' => null]);

        return response()->json(['ok' => true, 'mode' => $data['mode']]);
    }

    public function resumeHuman(Request $request, Conversation $conversation)
    {
        // Human takes over: disable AI
        $conversation->update(['ai_mode' => 'off', 'ai_handoff' => true, 'handoff_reason' => 'Human agent took over']);

        return response()->json(['ok' => true]);
    }

    public function resumeAi(Request $request, Conversation $conversation)
    {
        $conversation->update(['ai_mode' => 'assist', 'ai_handoff' => false, 'handoff_reason' => null]);

        return response()->json(['ok' => true]);
    }

    public function sendSuggestion(Request $request, Message $message)
    {
        $conversation = $message->conversation;
        $text = $message->ai_suggested_reply ?: $request->input('text');
        if (empty($text)) {
            return response()->json(['ok' => false, 'error' => 'No suggestion available.'], 422);
        }

        $contact = $conversation->contact;
        $result = $this->kapso->sendMessage($contact->phone, $text);
        $mid = $result['messages'][0]['id'] ?? null;
        if (! $mid) {
            return response()->json(['ok' => false, 'error' => 'Send failed.'], 422);
        }

        $new = Message::create([
            'conversation_id' => $conversation->id,
            'kapso_message_id' => $mid,
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $text,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        $conversation->update(['last_message_at' => now()]);

        return response()->json(['ok' => true, 'message' => [
            'id' => $new->id,
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $text,
            'media_url' => null,
            'status' => 'sent',
            'time' => now()->format('H:i'),
        ]]);
    }

    public function feedback(Request $request, Message $message)
    {
        $data = $request->validate([
            'rating' => 'required|integer|in:-1,1',
        ]);
        $message->update(['ai_feedback' => $data['rating']]);

        return response()->json(['ok' => true]);
    }

    public function analyzeNow(Request $request, Conversation $conversation, AiService $ai)
    {
        $lastInbound = $conversation->messages()->where('direction', 'inbound')->latest()->first();
        if (! $lastInbound) {
            return response()->json(['ok' => false, 'error' => 'No inbound message.'], 422);
        }
        $ai->handleInbound($lastInbound);

        return response()->json(['ok' => true]);
    }
}
