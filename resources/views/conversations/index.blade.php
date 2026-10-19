@extends('layouts.app')
@section('title', 'Inbox')
@section('content')
@php
    $convList = $conversations->map(fn ($c) => [
        'id' => $c->id,
        'name' => $c->contact->name ?? $c->contact->phone,
        'phone' => $c->contact->phone,
        'initial' => strtoupper(substr($c->contact->name ?? $c->contact->phone, 0, 1)),
        'last_message' => $c->latestMessage->body ?? 'No messages',
        'unread' => $c->unread_count,
        'time' => $c->last_message_at?->diffForHumans(null, true),
    ]);
    $selectedData = null;
    if ($selected) {
        $selectedData = [
            'id' => $selected->id,
            'name' => $selected->contact->name ?? $selected->contact->phone,
            'phone' => $selected->contact->phone,
            'initial' => strtoupper(substr($selected->contact->name ?? $selected->contact->phone, 0, 1)),
            'status' => $selected->status,
            'messages' => $selected->messages->map(fn ($m) => [
                'id' => $m->id,
                'direction' => $m->direction,
                'type' => $m->type,
                'body' => $m->body,
                'media_url' => $m->media_url,
                'status' => $m->status,
                'time' => $m->sent_at?->format('H:i'),
            ]),
        ];
    }
@endphp
<div class="flex h-[calc(100vh-8rem)] gap-3" x-data="inboxApp({{ $convList->toJson() }}, {{ json_encode($selectedData) }})" x-init="init()">
    <!-- Conversation list -->
    <div class="w-full lg:w-80 shrink-0 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col" :class="selected ? 'hidden lg:flex' : 'flex'">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">Chats</h3>
            <span class="text-xs text-slate-400" x-text="conversations.length + ' conversations'"></span>
        </div>
        <div class="flex-1 overflow-y-auto scrollbar-thin">
            <template x-for="c in conversations" :key="c.id">
                <button @click="select(c.id)" class="w-full flex items-center gap-3 px-4 py-3 border-b border-slate-100 hover:bg-slate-50 transition text-left" :class="selected && selected.id === c.id ? 'bg-emerald-50' : ''">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-semibold shrink-0" x-text="c.initial"></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="font-medium text-slate-900 truncate text-sm" x-text="c.name"></p>
                            <span class="text-[10px] text-slate-400" x-text="c.time"></span>
                        </div>
                        <p class="text-xs text-slate-500 truncate" x-text="c.last_message"></p>
                    </div>
                    <template x-if="c.unread > 0">
                        <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-emerald-500 text-white font-medium" x-text="c.unread"></span>
                    </template>
                </button>
            </template>
            <div x-show="conversations.length === 0" class="px-4 py-12 text-center text-slate-400 text-sm">No conversations yet</div>
        </div>
    </div>

    <!-- Chat panel -->
    <div class="flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col" :class="selected ? 'flex' : 'hidden lg:flex'">
        <template x-if="selected">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100">
                    <button @click="selected = null" class="lg:hidden p-2 rounded-xl hover:bg-slate-100">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-semibold" x-text="selected.contact.initial"></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-900 truncate" x-text="selected.contact.name"></p>
                        <p class="text-xs text-slate-500 truncate" x-text="selected.contact.phone"></p>
                    </div>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto bg-slate-50 p-3 space-y-2 scrollbar-thin" x-ref="box">
                    <template x-for="m in messages" :key="m.id">
                        <div class="flex" :class="m.direction === 'outbound' ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[70%] px-3 py-2 rounded-2xl text-sm shadow-sm" :class="m.direction === 'outbound' ? 'bg-emerald-500 text-white rounded-br-md' : 'bg-white text-slate-800 rounded-bl-md border border-slate-200'">
                                <template x-if="m.type === 'image' && m.media_url">
                                    <img :src="m.media_url" class="rounded-lg max-w-full mb-1" alt="">
                                </template>
                                <template x-if="m.type === 'video' && m.media_url">
                                    <video :src="m.media_url" controls class="rounded-lg max-w-full mb-1"></video>
                                </template>
                                <template x-if="m.type === 'audio' && m.media_url">
                                    <audio :src="m.media_url" controls class="w-full mb-1"></audio>
                                </template>
                                <template x-if="m.type === 'document' && m.media_url">
                                    <a :href="m.media_url" target="_blank" class="flex items-center gap-1 underline text-xs mb-1">📄 Download</a>
                                </template>
                                <template x-if="m.type === 'location'">
                                    <a :href="'https://maps.google.com/?q=' + encodeURIComponent(m.body || '')" target="_blank" class="flex items-center gap-1 underline text-xs mb-1">📍 View on map</a>
                                </template>
                                <template x-if="m.type === 'sticker' && m.media_url">
                                    <img :src="m.media_url" class="w-20 h-20 mb-1" alt="">
                                </template>
                                <p x-show="m.body" class="whitespace-pre-wrap break-words" x-text="m.body"></p>
                                <div class="flex items-center gap-1 mt-1" :class="m.direction === 'outbound' ? 'justify-end' : 'justify-start'">
                                    <span class="text-[9px] opacity-70" x-text="m.time"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="messages.length === 0" class="h-full flex items-center justify-center text-slate-400 text-sm">
                        No messages yet. Start the conversation.
                    </div>
                </div>

                <!-- Composer -->
                <div class="border-t border-slate-100 p-3" x-data="{ msgType: 'text' }">
                    <form @submit.prevent="send($event)" enctype="multipart/form-data" class="space-y-2">
                        <input type="hidden" name="type" :value="msgType">
                        <div class="flex gap-1 flex-wrap">
                            <template x-for="t in ['text','image','video','audio','document','location','sticker']" :key="t">
                                <button type="button" @click="msgType = t" class="px-2.5 py-1 rounded-lg text-[11px] font-medium transition" :class="msgType === t ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" x-text="t.charAt(0).toUpperCase() + t.slice(1)"></button>
                            </template>
                        </div>

                        <div x-show="msgType === 'text'">
                            <textarea name="body" rows="1" placeholder="Type a message..." class="w-full px-3 py-2 bg-slate-50 rounded-xl border border-slate-200 outline-none resize-none text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                        </div>

                        <div x-show="['image','video','audio','document','sticker'].includes(msgType)" class="space-y-2">
                            <input type="file" name="media_file" class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-medium hover:file:bg-emerald-100">
                            <input name="caption" type="text" placeholder="Caption (optional)" class="w-full px-3 py-2 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div x-show="msgType === 'location'" class="grid grid-cols-2 gap-2">
                            <input name="latitude" type="text" placeholder="Latitude" class="px-3 py-2 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500">
                            <input name="longitude" type="text" placeholder="Longitude" class="px-3 py-2 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500">
                            <input name="location_name" type="text" placeholder="Place name" class="px-3 py-2 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500 col-span-2">
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-500 text-white font-semibold hover:bg-emerald-600 transition shadow-sm flex items-center gap-1.5 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
        <template x-if="!selected">
            <div class="flex-1 flex items-center justify-center text-slate-400">
                <div class="text-center">
                    <div class="w-20 h-20 mx-auto rounded-3xl bg-slate-100 flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <p class="font-medium text-slate-500">Select a conversation</p>
                    <p class="text-sm text-slate-400 mt-1">Choose a chat from the list to start messaging.</p>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function inboxApp(convs, selected) {
    return {
        conversations: convs || [],
        selected: selected || null,
        messages: selected ? selected.messages : [],
        timer: null,
        async init() {
            if (this.selected) {
                this.scrollToBottom();
                this.timer = setInterval(() => this.poll(), 4000);
            }
        },
        async select(id) {
            if (this.selected && this.selected.id === id) return;
            this.selected = null;
            this.messages = [];
            try {
                const res = await fetch(`/conversations/${id}/data`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                const data = await res.json();
                this.selected = data;
                this.messages = data.messages || [];
                // update unread in list
                const c = this.conversations.find(x => x.id === id);
                if (c) c.unread = 0;
                this.$nextTick(() => this.scrollToBottom());
                if (this.timer) clearInterval(this.timer);
                this.timer = setInterval(() => this.poll(), 4000);
            } catch (e) { console.error(e); }
        },
        async poll() {
            if (!this.selected) return;
            const lastId = this.messages.length ? this.messages[this.messages.length - 1].id : 0;
            try {
                const res = await fetch(`/conversations/${this.selected.id}/poll?after_id=${lastId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.messages && data.messages.length) {
                    this.messages = this.messages.concat(data.messages);
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (e) { console.error(e); }
        },
        async send(e) {
            const form = e.target;
            const formData = new FormData(form);
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.ok && data.message) {
                    this.messages = this.messages.concat(data.message);
                    form.reset();
                    this.$nextTick(() => this.scrollToBottom());
                } else if (data.error) {
                    alert(data.error);
                }
            } catch (err) { console.error(err); alert('Send failed'); }
        },
        scrollToBottom() {
            const box = this.$refs.box;
            if (box) box.scrollTop = box.scrollHeight;
        }
    }
}
</script>
@endsection