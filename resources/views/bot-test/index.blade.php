@extends('layouts.app')
@section('title', 'Bot Tester')
@section('content')
<div class="max-w-3xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Bot Tester</h2>
        <p class="text-sm text-slate-500 mt-1">Simulate an inbound WhatsApp message and see how the AI bot responds.</p>
    </div>

    <form method="POST" action="{{ route('bot-test.run') }}" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone</label>
                <input name="phone" value="{{ old('phone', '923001234567') }}" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Name (optional)</label>
                <input name="name" value="{{ old('name', 'Test User') }}" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Inbound message</label>
            <textarea name="message" rows="3" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">{{ old('message', 'Hi, I want to know the price of your product.') }}</textarea>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">AI mode</label>
                <select name="mode" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
                    <option value="assist">Assist (suggest only)</option>
                    <option value="auto">Auto (auto-reply)</option>
                    <option value="off">Off</option>
                </select>
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="reset" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    Reset conversation first
                </label>
            </div>
        </div>
        <button class="px-6 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition">Run Test</button>
    </form>

    @if(isset($conversations) && $conversations->count())
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <h3 class="font-semibold text-slate-900 mb-3">Recent test conversations</h3>
            <ul class="space-y-2 text-sm">
                @foreach($conversations as $c)
                    <li class="flex justify-between text-slate-600">
                        <span>{{ $c->contact->name ?? $c->contact->phone }}</span>
                        <span class="text-slate-400">mode: {{ $c->ai_mode }} · tags: {{ implode(', ', $c->ai_tags ?? []) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection