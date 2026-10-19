@extends('layouts.app')
@section('title', 'New Template')
@section('content')
<div class="fade-in max-w-lg">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">New Template</h1>
    <form method="POST" action="{{ route('templates.store') }}" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input name="name" value="{{ old('name') }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Language</label>
            <input name="language" value="{{ old('language', 'en_US') }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
            <input name="category" value="{{ old('category') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
            <input name="status" value="{{ old('status', 'draft') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div class="flex gap-3">
            <button class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition">Save</button>
            <a href="{{ route('templates.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition">Cancel</a>
        </div>
    </form>
</div>
@endsection