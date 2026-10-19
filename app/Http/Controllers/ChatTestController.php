<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ChatTestController extends Controller
{
    public function __construct(protected AiService $ai) {}

    public function index()
    {
        $conversation = $this->currentConversation();

        return view('chat-test.index', compact('conversation'));
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:4096',
            'mode' => 'nullable|string|in:off,assist,auto',
            'reset' => 'nullable|boolean',
        ]);

        if ($request->boolean('reset')) {
            Session::forget('chat_test_conversation_id');
        }

        $phone = '923001234567';
        $contact = Contact::firstOrCreate(['phone' => $phone], ['name' => 'Test User']);

        $conversation = $this->currentConversation();
        if (! $conversation) {
            $conversation = Conversation::create([
                'contact_id' => $contact->id,
                'status' => 'open',
                'ai_mode' => $data['mode'] ?? 'auto',
            ]);
            Session::put('chat_test_conversation_id', $conversation->id);
        } elseif (isset($data['mode'])) {
            $conversation->update(['ai_mode' => $data['mode'], 'ai_handoff' => false, 'handoff_reason' => null]);
        }

        $inbound = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'type' => 'text',
            'body' => $data['message'],
            'status' => 'received',
            'sent_at' => now(),
        ]);

        $beforeOutboundId = $conversation->messages()->where('direction', 'outbound')->max('id') ?? 0;

        $before = microtime(true);
        $error = null;
        try {
            $this->ai->handleInbound($inbound, true);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
        $elapsed = round(microtime(true) - $before, 2);

        $inbound->refresh();
        $conversation->refresh();
        $outbound = $conversation->messages()
            ->where('direction', 'outbound')
            ->where('id', '>', $beforeOutboundId)
            ->latest()
            ->first();

        return response()->json([
            'ok' => true,
            'error' => $error,
            'elapsed' => $elapsed,
            'inbound' => [
                'id' => $inbound->id,
                'body' => $inbound->body,
                'tags' => $inbound->ai_tags ?? [],
                'confidence' => $inbound->ai_confidence,
                'suggested_reply' => $inbound->ai_suggested_reply,
                'handoff_triggered' => $inbound->ai_handoff_triggered,
                'time' => $inbound->sent_at?->format('H:i'),
            ],
            'outbound' => $outbound ? [
                'id' => $outbound->id,
                'body' => $outbound->body,
                'status' => $outbound->status,
                'time' => $outbound->sent_at?->format('H:i'),
            ] : null,
            'conversation' => [
                'ai_mode' => $conversation->ai_mode,
                'ai_handoff' => $conversation->ai_handoff,
                'handoff_reason' => $conversation->handoff_reason,
                'ai_tags' => $conversation->ai_tags ?? [],
                'lead_stage' => $conversation->lead_stage,
                'lead_score' => $conversation->lead_score,
            ],
        ]);
    }

    public function reset()
    {
        $convId = Session::get('chat_test_conversation_id');
        if ($convId) {
            Conversation::where('id', $convId)->delete();
        }
        Contact::where('phone', '923001234567')->each(function ($c) {
            Conversation::where('contact_id', $c->id)->delete();
            $c->metadata = null;
            $c->save();
        });
        Session::forget('chat_test_conversation_id');

        return response()->json(['ok' => true]);
    }

    protected function currentConversation(): ?Conversation
    {
        $id = Session::get('chat_test_conversation_id');
        if (! $id) {
            return null;
        }
        $conv = Conversation::find($id);
        if (! $conv) {
            Session::forget('chat_test_conversation_id');
        }

        return $conv;
    }
}
