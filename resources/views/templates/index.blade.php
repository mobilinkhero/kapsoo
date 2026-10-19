@extends('layouts.app')
@section('title', 'Templates')
@section('content')
<div class="fade-in">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Templates</h1>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('templates.sync') }}">@csrf
                <button class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition">Sync from Kapso</button>
            </form>
            <a href="{{ route('templates.create') }}" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition">New Template</a>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">Name</th>
                    <th class="text-left px-6 py-3 font-medium">Language</th>
                    <th class="text-left px-6 py-3 font-medium">Category</th>
                    <th class="text-left px-6 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($templates as $t)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-3 font-medium text-slate-900">{{ $t->name }}</td>
                        <td class="px-6 py-3 text-slate-600">{{ $t->language }}</td>
                        <td class="px-6 py-3 text-slate-600">{{ $t->category ?? '—' }}</td>
                        <td class="px-6 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-700">{{ $t->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400">No templates yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $templates->links() }}</div>
</div>
@endsection