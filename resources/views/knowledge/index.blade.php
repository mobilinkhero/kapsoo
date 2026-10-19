@extends('layouts.app')
@section('title', 'Knowledge Base')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Knowledge Base</h2>
            <p class="text-sm text-slate-500 mt-1">Documents used by the AI for RAG</p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('knowledge.reindexAll') }}" class="inline">
                @csrf
                <button class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">Reindex All</button>
            </form>
            <a href="{{ route('knowledge.test') }}" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">Test RAG</a>
            <a href="{{ route('knowledge.create') }}" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-600 transition">+ Add Document</a>
        </div>
    </div>

    <form method="POST" action="{{ route('knowledge.importUrl') }}" class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-col sm:flex-row gap-3">
        @csrf
        <input name="url" type="url" required placeholder="https://example.com/article" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        <input name="title" placeholder="Title (optional)" class="sm:w-48 px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        <button class="px-5 py-2.5 rounded-xl bg-slate-900 text-white font-semibold hover:bg-slate-800 transition">Import URL</button>
    </form>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Title</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="text-left px-4 py-3">Active</th>
                    <th class="text-left px-4 py-3">Updated</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($documents as $doc)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $doc->title }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $doc->type }}</td>
                        <td class="px-4 py-3">{{ $doc->active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $doc->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('knowledge.edit', $doc) }}" class="text-emerald-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('knowledge.destroy', $doc) }}" class="inline" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No knowledge documents yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $documents->links() }}</div>
</div>
@endsection