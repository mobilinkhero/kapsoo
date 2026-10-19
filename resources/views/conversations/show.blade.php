@extends('layouts.app')
@section('title', 'Conversation')
@section('content')
<div class="fade-in flex flex-col h-[calc(100vh-8rem)]">
    <div class="flex items-center gap-3 mb-4">
        <a href="{{ route('conversations.index') }}" class="p-2 rounded-lg hover:bg-slate-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-semibold">
            {{ strtoupper(substr($conversation->contact->name ?? $conversation->contact->phone, 0, 1)) }}
        </div>
        <div>
            <p class="font-semibold text-slate-900">{{ $conversation->contact->name ?? $conversation->contact->phone }}</p>
            <p class="text-xs text-slate-500">{{ $conversation->contact->phone }}</p>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto bg-white rounded-2xl border border-slate-200 p-4 space-y-3" id="chatBox">
        @foreach($conversation->messages as $m)
            <div class="flex {{ $m->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[75%] px-4 py-2.5 rounded-2xl {{ $m->direction === 'outbound' ? 'bg-emerald-600 text-white rounded-br-sm' : 'bg-slate-100 text-slate-800 rounded-bl-sm' }} shadow-sm">
                    <p class="text-sm whitespace-pre-wrap">{{ $m->body }}</p>
                    <p class="text-[10px] mt-1 opacity-70 text-right">{{ $m->sent_at?->format('H:i') }} · {{ $m->status }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('conversations.send', $conversation) }}" class="mt-4 flex gap-2">
        @csrf
        <input name="body" required placeholder="Type a message..." class="flex-1 px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        <button class="px-5 py-3 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-sm">Send</button>
    </form>
</div>
<script>const cb = document.getElementById('chatBox'); if (cb) cb.scrollTop = cb.scrollHeight;</script>
@endsection