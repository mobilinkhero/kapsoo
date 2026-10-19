@extends('layouts.app')
@section('title', 'Add Knowledge')
@section('content')
<div class="max-w-2xl">
    <h2 class="text-2xl font-bold text-slate-900 mb-4">Add Knowledge Document</h2>
    <form method="POST" action="{{ route('knowledge.store') }}" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Title</label>
            <input name="title" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Type</label>
            <select name="type" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
                <option value="text">Text</option>
                <option value="faq">FAQ</option>
                <option value="playbook">Playbook</option>
                <option value="product">Product</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Source (optional)</label>
            <input name="source" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Content</label>
            <textarea name="content" rows="12" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none"></textarea>
        </div>
        <div class="flex gap-3">
            <button class="px-6 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">Save & Index</button>
            <a href="{{ route('knowledge.index') }}" class="px-6 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">Cancel</a>
        </div>
    </form>
</div>
@endsection