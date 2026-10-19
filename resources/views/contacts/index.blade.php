@extends('layouts.app')
@section('title', 'Contacts')
@section('content')
<div class="fade-in">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Contacts</h1>
        <a href="{{ route('contacts.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Contact
        </a>
    </div>

    <form method="GET" class="mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search contacts..." class="w-full sm:w-80 px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">Name</th>
                    <th class="text-left px-6 py-3 font-medium">Phone</th>
                    <th class="text-left px-6 py-3 font-medium">Last seen</th>
                    <th class="text-right px-6 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($contacts as $contact)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-3 font-medium text-slate-900">{{ $contact->name ?? '—' }}</td>
                        <td class="px-6 py-3 text-slate-600">{{ $contact->phone }}</td>
                        <td class="px-6 py-3 text-slate-500">{{ $contact->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                        <td class="px-6 py-3 text-right space-x-2">
                            <a href="{{ route('contacts.edit', $contact) }}" class="text-emerald-600 hover:underline">Edit</a>
                            <form action="{{ route('contacts.destroy', $contact) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400">No contacts yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $contacts->links() }}</div>
</div>
@endsection