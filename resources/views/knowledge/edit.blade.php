@extends('layouts.app')
@section('title', 'Edit Knowledge')
@section('content')
<div class="max-w-2xl">
    <h2 class="text-2xl font-bold text-slate-900 mb-4">Edit Knowledge Document</h2>
    <form method="POST" action="{{ route('knowledge.update', $knowledgeDocument) }}" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Title</label>
            <input name="title" value="{{ $knowledgeDocument->title }}" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Type</label>
            <select name="type" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                @foreach(['text','faq','playbook','product'] as $t)
                    <option value="{{ $t }}" {{ $knowledgeDocument->type === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Source (optional)</label>
            <input name="source" value="{{ $knowledgeDocument->source }}" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Content</label>
            <textarea name="content" rows="12" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none">{{ $knowledgeDocument->content }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="active" value="1" {{ $knowledgeDocument->active ? 'checked' : '' }} class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            Active
        </label>
        <div class="flex gap-3">
            <button class="px-6 py-3 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">Update & Re-index</button>
            <a href="{{ route('knowledge.index') }}" class="px-6 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">Cancel</a>
        </div>
    </form>
</div>
@endsection