<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $contacts = Contact::when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('contacts.index', compact('contacts'));
    }

    public function create()
    {
        return view('contacts.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50|unique:contacts,phone',
        ]);

        Contact::create($data);

        return redirect()->route('contacts.index')->with('success', 'Contact created.');
    }

    public function show(Contact $contact)
    {
        $contact->load('conversations');

        return view('contacts.show', compact('contact'));
    }

    public function edit(Contact $contact)
    {
        return view('contacts.edit', compact('contact'));
    }

    public function update(Request $request, Contact $contact)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50|unique:contacts,phone,'.$contact->id,
        ]);

        $contact->update($data);

        return redirect()->route('contacts.index')->with('success', 'Contact updated.');
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Contact deleted.');
    }
}
