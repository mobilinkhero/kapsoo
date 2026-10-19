@extends('layouts.app')
@section('title', 'Broadcasts')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Broadcasts</h2>
            <p class="text-sm text-slate-500 mt-1">Send templates to many recipients</p>
        </div>
        <a href="{{ route('broadcasts.create') }}" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-600 text-white font-semibold hover:from-emerald-600 hover:to-emerald-700 transition shadow-lg shadow-emerald-500/25">New Broadcast</a>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80 text-slate-600">
                    <tr>
                        <th class="text-left px-6 py-3.5 font-semibold">Name</th>
                        <th class="text-left px-6 py-3.5 font-semibold">Template</th>
                        <th class="text-left px-6 py-3.5 font-semibold">Status</th>
                        <th class="text-left px-6 py-3.5 font-semibold">Sent / Failed</th>
                        <th class="text-right px-6 py-3.5 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($broadcasts as $b)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $b->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $b->template->name ?? '—' }}</td>
                            <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ $b->status }}</span></td>
                            <td class="px-6 py-4 text-slate-600">{{ $b->sent_count }} / {{ $b->failed_count }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('broadcasts.show', $b) }}" class="text-emerald-600 hover:text-emerald-600 font-medium">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-16 text-center">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13.47a4.5 4.5 0 000-2.94M19 18a7.5 7.5 0 000-12M13 6.5a3 3 0 00-3 3v7a3 3 0 003 3"/></svg>
                            </div>
                            <p class="text-slate-500 font-medium">No broadcasts yet</p>
                            <p class="text-sm text-slate-400 mt-1">Create your first broadcast campaign.</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $broadcasts->links() }}</div>
</div>
@endsection