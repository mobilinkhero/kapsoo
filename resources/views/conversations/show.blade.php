@extends('layouts.app')
@section('title', 'Conversation')
@section('content')
@php
    $initialMessages = $conversation->messages->map(fn ($m) => [
        'id' => $m->id,
        'direction' => $m->direction,
        'type' => $m->type,
        'body' => $m->body,
        'media_url' => $m->media_url,
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
                        <!-- Image -->
                        <template x-if="m.type === 'image' && m.media_url">
                            <img :src="m.media_url" class="rounded-lg max-w-full mb-2" alt="image">
                        </template>
                        <!-- Video -->
                        <template x-if="m.type === 'video' && m.media_url">
                            <video :src="m.media_url" controls class="rounded-lg max-w-full mb-2"></video>
                        </template>
                        <!-- Audio -->
                        <template x-if="m.type === 'audio' && m.media_url">
                            <audio :src="m.media_url" controls class="w-full mb-2"></audio>
                        </template>
                        <!-- Document -->
                        <template x-if="m.type === 'document' && m.media_url">
                            <a :href="m.media_url" target="_blank" class="flex items-center gap-2 underline text-sm mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download document
                            </a>
                        </template>
                        <!-- Location -->
                        <template x-if="m.type === 'location'">
                            <a :href="'https://maps.google.com/?q=' + (m.body || '')" target="_blank" class="flex items-center gap-2 underline text-sm mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                View on map
                            </a>
                        </template>
                        <!-- Sticker -->
                        <template x-if="m.type === 'sticker' && m.media_url">
                            <img :src="m.media_url" class="w-24 h-24 mb-2" alt="sticker">
                        </template>
                        <!-- Text / caption -->
                        <p x-show="m.body" class="text-sm whitespace-pre-wrap leading-relaxed" x-text="m.body"></p>
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
    <div class="mt-4 bg-white rounded-2xl border border-slate-200 shadow-sm p-3" x-data="{ msgType: 'text' }">
        <form method="POST" action="{{ route('conversations.send', $conversation) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="type" :value="msgType">
            <div class="flex gap-2 flex-wrap">
                <template x-for="t in ['text','image','video','audio','document','location','sticker']" :key="t">
                    <button type="button" @click="msgType = t" class="px-3 py-1.5 rounded-lg text-xs font-medium transition" :class="msgType === t ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" x-text="t.charAt(0).toUpperCase() + t.slice(1)"></button>
                </template>
            </div>

            <!-- Text -->
            <div x-show="msgType === 'text'">
                <textarea name="body" rows="2" placeholder="Type a message..." class="w-full px-4 py-3 bg-slate-50 rounded-xl border border-slate-200 outline-none resize-none text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <!-- Media (image/video/audio/document/sticker) -->
            <div x-show="['image','video','audio','document','sticker'].includes(msgType)" class="space-y-2">
                <input name="media_url" type="url" placeholder="Media URL (https://...)" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500">
                <input name="caption" type="text" placeholder="Caption (optional)" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500">
                <input name="body" type="text" placeholder="Filename (documents only, optional)" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- Location -->
            <div x-show="msgType === 'location'" class="grid grid-cols-2 gap-2">
                <input name="latitude" type="text" placeholder="Latitude" class="px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500">
                <input name="longitude" type="text" placeholder="Longitude" class="px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500">
                <input name="location_name" type="text" placeholder="Place name (optional)" class="px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500">
                <input name="location_address" type="text" placeholder="Address (optional)" class="px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold hover:from-emerald-700 hover:to-teal-700 transition shadow-lg shadow-emerald-500/25 flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Send
                </button>
            </div>
        </form>
    </div>
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