@extends('layouts.app')
@section('title', 'Conversation')
@section('content')
@php
    $initialMessages = $conversation->messages->map(fn ($m) => [
        'id' => $m->id,
        'direction' => $m->direction,
        'body' => $m->body,
        'status' => $m->status,
        'time' => $m->sent_at?->format('H:i'),
    ]);
@endphp
<div class="flex flex-col h-[calc(100vh-8rem)] max-w-4xl mx-auto" x-data="chatPoll({{ $conversation->id }}, {{ $initialMessages->toJson() }})" x-init="init()">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-4 bg-white rounded-2xl border border-slate-200 px-4 py-3 shadow-sm">
        <a href="{{ route('conversations.index') }}" class="p-2 rounded-xl hover:bg-slate-100 transition">
            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-semibold shadow">
            {{ strtoupper(substr($conversation->contact->name ?? $conversation->contact->phone, 0, 1)) }}
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-slate-900 truncate">{{ $conversation->contact->name ?? $conversation->contact->phone }}</p>
            <p class="text-xs text-slate-500 truncate">{{ $conversation->contact->phone }} · {{ $conversation->status }}</p>
        </div>
        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $conversation->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($conversation->status) }}</span>
    </div>

    <!-- Chat -->
    <div class="flex-1 overflow-y-auto bg-gradient-to-b from-slate-50 to-white rounded-2xl border border-slate-200 p-4 space-y-3 scrollbar-thin" x-ref="box">
        <template x-for="m in messages" :key="m.id">
            <div class="flex" :class="m.direction === 'outbound' ? 'justify-end' : 'justify-start'">
                <div class="max-w-[78%]">
                    <div class="px-4 py-2.5 rounded-2xl shadow-sm" :class="m.direction === 'outbound' ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-br-md' : 'bg-white text-slate-800 rounded-bl-md border border-slate-200'">
                        <p class="text-sm whitespace-pre-wrap leading-relaxed" x-text="m.body"></p>
                    </div>
                    <div class="flex items-center gap-1 mt-1 px-1" :class="m.direction === 'outbound' ? 'justify-end' : 'justify-start'">
                        <span class="text-[10px] text-slate-400" x-text="m.time"></span>
                        <template x-if="m.direction === 'outbound'">
                            <span class="text-[10px]" :class="m.status === 'read' ? 'text-blue-500' : 'text-slate-400'" x-text="m.status === 'read' ? '✓✓' : (m.status === 'delivered' ? '✓✓' : '✓')"></span>
                        </template>
                    </div>
                </div>
            </div>
        </template>
        <div x-show="messages.length === 0" class="h-full flex items-center justify-center text-slate-400">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <p class="font-medium">No messages yet</p>
                <p class="text-sm mt-1">Start the conversation below.</p>
            </div>
        </div>
    </div>

    <!-- Composer -->
    <form method="POST" action="{{ route('conversations.send', $conversation) }}" class="mt-4 flex gap-2 items-end">
        @csrf
        <div class="flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm focus-within:ring-2 focus-within:ring-emerald-500 transition">
            <textarea name="body" rows="1" placeholder="Type a message..." required class="w-full px-4 py-3 bg-transparent outline-none resize-none text-sm"></textarea>
        </div>
        <button type="submit" class="px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold hover:from-emerald-700 hover:to-teal-700 transition shadow-lg shadow-emerald-500/25 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            Send
        </button>
    </form>
</div>

<script>
function chatPoll(convId, initial) {
    return {
        messages: initial || [],
        timer: null,
        init() {
            this.scrollToBottom();
            this.timer = setInterval(() => this.poll(), 4000);
        },
        async poll() {
            const lastId = this.messages.length ? this.messages[this.messages.length - 1].id : 0;
            try {
                const res = await fetch(`/conversations/${convId}/poll?after_id=${lastId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.messages && data.messages.length) {
                    this.messages = this.messages.concat(data.messages);
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (e) { console.error(e); }
        },
        scrollToBottom() {
            const box = this.$refs.box;
            if (box) box.scrollTop = box.scrollHeight;
        }
    }
}
</script>
@endsection