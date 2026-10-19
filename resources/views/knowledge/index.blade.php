@extends('layouts.app')
@section('title', 'Knowledge Base')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Knowledge Base</h2>
            <p class="text-sm text-slate-500 mt-1">Documents used by the AI for RAG</p>
        </div>
        <a href="{{ route('knowledge.create') }}" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">+ Add Document</a>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
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
                            <a href="{{ route('knowledge.edit', $doc) }}" class="text-indigo-600 hover:underline">Edit</a>
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