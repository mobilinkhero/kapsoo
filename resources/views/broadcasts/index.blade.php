@extends('layouts.app')
@section('title', 'Broadcasts')
@section('content')
<div class="fade-in">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Broadcasts</h1>
        <a href="{{ route('broadcasts.create') }}" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition">New Broadcast</a>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">Name</th>
                    <th class="text-left px-6 py-3 font-medium">Template</th>
                    <th class="text-left px-6 py-3 font-medium">Status</th>
                    <th class="text-left px-6 py-3 font-medium">Sent / Failed</th>
                    <th class="text-right px-6 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($broadcasts as $b)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-3 font-medium text-slate-900">{{ $b->name }}</td>
                        <td class="px-6 py-3 text-slate-600">{{ $b->template->name ?? '—' }}</td>
                        <td class="px-6 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-700">{{ $b->status }}</span></td>
                        <td class="px-6 py-3 text-slate-600">{{ $b->sent_count }} / {{ $b->failed_count }}</td>
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('broadcasts.show', $b) }}" class="text-emerald-600 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-400">No broadcasts yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $broadcasts->links() }}</div>
</div>
@endsection