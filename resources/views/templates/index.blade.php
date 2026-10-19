@extends('layouts.app')
@section('title', 'Templates')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Templates</h2>
            <p class="text-sm text-slate-500 mt-1">Manage WhatsApp message templates</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('templates.sync') }}">@csrf
                <button class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-medium hover:bg-slate-50 transition shadow-sm">Sync from Kapso</button>
            </form>
            <a href="{{ route('templates.create') }}" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold hover:from-emerald-700 hover:to-teal-700 transition shadow-lg shadow-emerald-500/25">New Template</a>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80 text-slate-600">
                    <tr>
                        <th class="text-left px-6 py-3.5 font-semibold">Name</th>
                        <th class="text-left px-6 py-3.5 font-semibold">Language</th>
                        <th class="text-left px-6 py-3.5 font-semibold">Category</th>
                        <th class="text-left px-6 py-3.5 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($templates as $t)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $t->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $t->language }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $t->category ?? '—' }}</td>
                            <td class="px-6 py-4"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ $t->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-16 text-center">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <p class="text-slate-500 font-medium">No templates yet</p>
                            <p class="text-sm text-slate-400 mt-1">Sync from Kapso or create one manually.</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $templates->links() }}</div>
</div>
@endsection