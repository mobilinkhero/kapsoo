@extends('layouts.app')
@section('title', 'New Broadcast')
@section('content')
<div class="fade-in max-w-2xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">New Broadcast</h1>
    <form method="POST" action="{{ route('broadcasts.store') }}" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input name="name" value="{{ old('name') }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Template</label>
            <select name="template_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
                @foreach($templates as $t)
                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->language }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Recipients</label>
            <select name="contact_ids[]" multiple required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none" size="8">
                @foreach($contacts as $c)
                    <option value="{{ $c->id }}">{{ $c->name ?? $c->phone }} ({{ $c->phone }})</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-400 mt-1">Hold Ctrl/Cmd to select multiple.</p>
        </div>
        <div class="flex gap-3">
            <button class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-600 transition">Create</button>
            <a href="{{ route('broadcasts.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition">Cancel</a>
        </div>
    </form>
</div>
@endsection