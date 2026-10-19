<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use App\Models\BroadcastRecipient;
use App\Models\Contact;
use App\Models\Template;
use App\Services\KapsoService;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    public function index()
    {
        $broadcasts = Broadcast::with('template')->orderByDesc('created_at')->paginate(20);

        return view('broadcasts.index', compact('broadcasts'));
    }

    public function create()
    {
        $templates = Template::all();
        $contacts = Contact::all();

        return view('broadcasts.create', compact('templates', 'contacts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'template_id' => 'required|exists:templates,id',
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $broadcast = Broadcast::create([
            'name' => $data['name'],
            'template_id' => $data['template_id'],
            'status' => 'draft',
        ]);

        foreach ($data['contact_ids'] as $cid) {
            BroadcastRecipient::create([
                'broadcast_id' => $broadcast->id,
                'contact_id' => $cid,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('broadcasts.show', $broadcast)->with('success', 'Broadcast created.');
    }

    public function show(Broadcast $broadcast)
    {
        $broadcast->load('template', 'recipients.contact');

        return view('broadcasts.show', compact('broadcast'));
    }

    public function send(Broadcast $broadcast, KapsoService $kapso)
    {
        $template = $broadcast->template;
        if (! $template) {
            return redirect()->back()->with('error', 'No template selected.');
        }

        $broadcast->update(['status' => 'sending']);

        foreach ($broadcast->recipients()->where('status', 'pending')->get() as $recipient) {
            $contact = $recipient->contact;
            $result = $kapso->sendTemplate($contact->phone, $template->name, $template->language);
            $messageId = $result['messages'][0]['id'] ?? null;

            if ($messageId) {
                $recipient->update(['status' => 'sent', 'message_id' => null]);
                $broadcast->increment('sent_count');
            } else {
                $recipient->update(['status' => 'failed', 'error' => json_encode($result)]);
                $broadcast->increment('failed_count');
            }
        }

        $broadcast->update(['status' => 'completed']);

        return redirect()->route('broadcasts.show', $broadcast)->with('success', 'Broadcast sent.');
    }
}
