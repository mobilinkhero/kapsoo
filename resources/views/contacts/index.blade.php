@extends('layouts.app')
@section('title', 'Contacts')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Contacts</h2>
            <p class="text-sm text-slate-500 mt-1">Manage your WhatsApp contacts</p>
        </div>
        <a href="{{ route('contacts.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold text-sm hover:from-emerald-700 hover:to-teal-700 transition shadow-lg shadow-emerald-500/25">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Contact
        </a>
    </div>

    <form method="GET" class="relative max-w-md">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or phone..." class="w-full pl-12 pr-4 py-3 rounded-2xl bg-white border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none shadow-sm transition">
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80 text-slate-600">
                    <tr>
                        <th class="text-left px-6 py-3.5 font-semibold">Contact</th>
                        <th class="text-left px-6 py-3.5 font-semibold">Phone</th>
                        <th class="text-left px-6 py-3.5 font-semibold">Last seen</th>
                        <th class="text-right px-6 py-3.5 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($contacts as $contact)
                        <tr class="hover:bg-slate-50/60 transition group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center text-slate-600 font-semibold group-hover:from-emerald-400 group-hover:to-teal-500 group-hover:text-white transition">
                                        {{ strtoupper(substr($contact->name ?? $contact->phone, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-slate-900">{{ $contact->name ?? 'Unnamed' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-mono text-xs">{{ $contact->phone }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $contact->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <a href="{{ route('contacts.edit', $contact) }}" class="text-emerald-600 hover:text-emerald-700 font-medium">Edit</a>
                                <form action="{{ route('contacts.destroy', $contact) }}" method="POST" class="inline" onsubmit="return confirm('Delete this contact?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-600 font-medium">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <p class="text-slate-500 font-medium">No contacts yet</p>
                                <p class="text-sm text-slate-400 mt-1">Add your first contact to get started.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $contacts->links() }}</div>
</div>
@endsection