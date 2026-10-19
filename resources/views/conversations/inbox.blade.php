@extends('layouts.app')
@section('title', 'Inbox')
@section('content')
<div class="flex h-[calc(100vh-8rem)] gap-3">
    <!-- Conversation list -->
    <div class="w-full lg:w-80 shrink-0 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-4 py-3 border-b border-slate-100">
            <h3 class="font-semibold text-slate-900">Chats</h3>
        </div>
        <div class="flex-1 overflow-y-auto scrollbar-thin">
            @forelse($conversations as $conv)
                <a href="{{ route('conversations.index', ['c' => $conv->id]) }}" class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 hover:bg-slate-50 transition {{ $selected && $selected->id === $conv->id ? 'bg-emerald-50' : '' }}">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-semibold shrink-0 text-sm">
                        {{ strtoupper(substr($conv->contact->name ?? $conv->contact->phone, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="font-medium text-slate-900 truncate text-sm">{{ $conv->contact->name ?? $conv->contact->phone }}</p>
                            <span class="text-[10px] text-slate-400">{{ $conv->last_message_at?->diffForHumans(null, true) }}</span>
                        </div>
                        <p class="text-xs text-slate-500 truncate">{{ $conv->latestMessage->body ?? 'No messages' }}</p>
                    </div>
                    @if($conv->unread_count > 0)
                        <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-emerald-500 text-white font-medium">{{ $conv->unread_count }}</span>
                    @endif
                </a>
            @empty
                <div class="px-4 py-12 text-center text-slate-400 text-sm">No conversations yet</div>
            @endforelse
        </div>
    </div>

    <!-- Chat panel -->
    <div class="flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        @if($selected)
            <!-- Header -->
            <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-semibold">
                    {{ strtoupper(substr($selected->contact->name ?? $selected->contact->phone, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-900 truncate">{{ $selected->contact->name ?? $selected->contact->phone }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ $selected->contact->phone }}</p>
                </div>
            </div>

            <!-- Messages -->
            <div class="flex-1 overflow-y-auto bg-slate-50 p-3 space-y-2 scrollbar-thin">
                @foreach($selected->messages as $m)
                    <div class="flex {{ $m->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[70%] px-3 py-2 rounded-2xl text-sm shadow-sm {{ $m->direction === 'outbound' ? 'bg-emerald-500 text-white rounded-br-md' : 'bg-white text-slate-800 rounded-bl-md border border-slate-200' }}">
                            @if($m->type === 'image' && $m->media_url)
                                <img src="{{ $m->media_url }}" class="rounded-lg max-w-full mb-1" alt="">
                            @endif
                            @if($m->type === 'video' && $m->media_url)
                                <video src="{{ $m->media_url }}" controls class="rounded-lg max-w-full mb-1"></video>
                            @endif
                            @if($m->type === 'audio' && $m->media_url)
                                <audio src="{{ $m->media_url }}" controls class="w-full mb-1"></audio>
                            @endif
                            @if($m->type === 'document' && $m->media_url)
                                <a href="{{ $m->media_url }}" target="_blank" class="flex items-center gap-1 underline text-xs mb-1">📄 Download</a>
                            @endif
                            @if($m->type === 'location')
                                <a href="https://maps.google.com/?q={{ urlencode($m->body ?? '') }}" target="_blank" class="flex items-center gap-1 underline text-xs mb-1">📍 View on map</a>
                            @endif
                            @if($m->type === 'sticker' && $m->media_url)
                                <img src="{{ $m->media_url }}" class="w-20 h-20 mb-1" alt="">
                            @endif
                            @if($m->body)
                                <p class="whitespace-pre-wrap break-words">{{ $m->body }}</p>
                            @endif
                            <div class="flex items-center gap-1 mt-1 {{ $m->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                                <span class="text-[9px] opacity-70">{{ $m->sent_at?->format('H:i') }}</span>
                                @if($m->direction === 'outbound')
                                    <span class="text-[9px] opacity-70">{{ $m->status === 'read' ? '✓✓' : ($m->status === 'delivered' ? '✓✓' : '✓') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
                @if($selected->messages->isEmpty())
                    <div class="h-full flex items-center justify-center text-slate-400 text-sm">No messages yet. Start the conversation.</div>
                @endif
            </div>

            <!-- Composer -->
            <div class="border-t border-slate-100 p-3">
                <form method="POST" action="{{ route('conversations.send', $selected) }}" enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <input type="hidden" name="type" value="text" id="msgType">
                    <div class="flex gap-1 flex-wrap mb-2">
                        @foreach(['text','image','video','audio','document','location','sticker'] as $t)
                            <button type="button" onclick="document.getElementById('msgType').value='{{ $t }}'; document.querySelectorAll('.type-btn').forEach(b=>b.classList.remove('bg-emerald-500','text-white')); this.classList.add('bg-emerald-500','text-white');" class="type-btn px-2.5 py-1 rounded-lg text-[11px] font-medium transition {{ $t === 'text' ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">{{ ucfirst($t) }}</button>
                        @endforeach
                    </div>

                    <div id="field-text">
                        <textarea name="body" rows="1" placeholder="Type a message..." class="w-full px-3 py-2 bg-slate-50 rounded-xl border border-slate-200 outline-none resize-none text-sm focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>
                    <div id="field-media" class="space-y-2 hidden">
                        <input type="file" name="media_file" class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:text-emerald-700 file:font-medium hover:file:bg-emerald-100">
                        <input name="caption" type="text" placeholder="Caption (optional)" class="w-full px-3 py-2 rounded-xl border border-slate-200 outline-none text-sm focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div id="field-location" class="grid grid-cols-2 gap-2 hidden">
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
        @else
            <div class="flex-1 flex items-center justify-center text-slate-400">
                <div class="text-center">
                    <div class="w-20 h-20 mx-auto rounded-3xl bg-slate-100 flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <p class="font-medium text-slate-500">Select a conversation</p>
                    <p class="text-sm text-slate-400 mt-1">Choose a chat from the list to start messaging.</p>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
// Toggle composer fields based on selected type
document.querySelectorAll('.type-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const type = this.textContent.toLowerCase();
        document.getElementById('field-text').classList.toggle('hidden', type !== 'text');
        document.getElementById('field-media').classList.toggle('hidden', !['image','video','audio','document','sticker'].includes(type));
        document.getElementById('field-location').classList.toggle('hidden', type !== 'location');
    });
});
</script>
@endsection