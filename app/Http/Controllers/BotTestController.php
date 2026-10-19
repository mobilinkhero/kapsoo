<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\AiService;
use Illuminate\Http\Request;

class BotTestController extends Controller
{
    public function __construct(protected AiService $ai) {}

    public function index()
    {
        $conversations = Conversation::with('contact')->orderByDesc('last_message_at')->limit(20)->get();

        return view('bot-test.index', compact('conversations'));
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'name' => 'nullable|string|max:255',
            'message' => 'required|string|max:4096',
            'mode' => 'required|string|in:off,assist,auto',
            'reset' => 'nullable|boolean',
        ]);

        $contact = Contact::firstOrCreate(
            ['phone' => $data['phone']],
            ['name' => $data['name'] ?? null]
        );
        if (! empty($data['name'])) {
            $contact->name = $data['name'];
            $contact->save();
        }

        if ($request->boolean('reset')) {
            Conversation::where('contact_id', $contact->id)->delete();
        }

        $conversation = Conversation::where('contact_id', $contact->id)->latest()->first();
        if (! $conversation) {
            $conversation = Conversation::create([
                'contact_id' => $contact->id,
                'status' => 'open',
                'ai_mode' => $data['mode'],
            ]);
        } else {
            $conversation->update(['ai_mode' => $data['mode'], 'ai_handoff' => false, 'handoff_reason' => null]);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'type' => 'text',
            'body' => $data['message'],
            'status' => 'received',
            'sent_at' => now(),
        ]);

        // Run AI synchronously for testing
        $before = microtime(true);
        try {
            $this->ai->handleInbound($message, true);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
        $elapsed = round(microtime(true) - $before, 2);

        $message->refresh();
        $conversation->refresh();
        $conversation->load('contact', 'messages');

        $outbound = $conversation->messages()->where('direction', 'outbound')->latest()->first();

        return view('bot-test.result', [
            'contact' => $contact,
            'conversation' => $conversation,
            'inbound' => $message,
            'outbound' => $outbound,
            'elapsed' => $elapsed,
            'error' => $error ?? null,
        ]);
    }
}
