@extends('layouts.app')
@section('title', 'Inbox')
@section('content')
<div class="flex h-[calc(100vh-8rem)] bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <!-- Sidebar: conversation list -->
    <div class="w-full lg:w-[320px] xl:w-[360px] flex flex-col border-r border-slate-200 bg-white">
        <div class="px-4 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900">Chats</h2>
            <span class="text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ $conversations->total() }}</span>
        </div>
        <div class="flex-1 overflow-y-auto">
            @forelse($conversations as $conv)
                <a href="{{ route('conversations.index', ['c' => $conv->id]) }}"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition border-b border-slate-50 {{ $selected && $selected->id === $conv->id ? 'bg-emerald-50/70 border-l-4 border-l-emerald-500' : '' }}">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-bold text-lg shrink-0 shadow-sm">
                        {{ strtoupper(substr($conv->contact->name ?? $conv->contact->phone, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-0.5">
                            <p class="font-semibold text-slate-900 truncate text-[15px]">{{ $conv->contact->name ?? $conv->contact->phone }}</p>
                            <span class="text-[11px] text-slate-400 shrink-0 ml-2">{{ $conv->last_message_at?->format('H:i') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-slate-500 truncate">{{ $conv->latestMessage->body ?? 'No messages yet' }}</p>
                            @if($conv->unread_count > 0)
                                <span class="ml-2 shrink-0 w-5 h-5 rounded-full bg-emerald-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $conv->unread_count }}</span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="flex flex-col items-center justify-center h-full text-slate-400 px-6">
                    <svg class="w-16 h-16 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    <p class="font-medium">No conversations yet</p>
                    <p class="text-xs mt-1">Messages will appear here</p>
                </div>
            @endforelse
        </div>
        <div class="px-4 py-2 border-t border-slate-100">
            {{ $conversations->links() }}
        </div>
    </div>

    <!-- Chat area -->
    <div class="flex-1 flex flex-col min-w-0 bg-[#efeae2]">
        @if($selected)
            <!-- Chat header -->
            <div class="bg-white px-4 py-3 border-b border-slate-200 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-bold shadow-sm">
                    {{ strtoupper(substr($selected->contact->name ?? $selected->contact->phone, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-900 truncate">{{ $selected->contact->name ?? $selected->contact->phone }}</p>
                    <p class="text-xs text-slate-500">{{ $selected->contact->phone }}</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full {{ $selected->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($selected->status) }}</span>
            </div>

            <!-- Messages -->
            <div class="flex-1 overflow-y-auto px-4 py-3 space-y-1" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23d4d4d4\' fill-opacity=\'0.15\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
                @foreach($selected->messages as $m)
                    <div class="flex {{ $m->direction === 'outbound' ? 'justify-end' : 'justify-start' }} mb-1">
                        <div class="max-w-[65%] px-3 py-1.5 rounded-lg shadow-sm text-sm {{ $m->direction === 'outbound' ? 'bg-[#d9fdd3] text-slate-900 rounded-tr-none' : 'bg-white text-slate-900 rounded-tl-none' }}">
                            @if($m->type === 'image' && $m->media_url)
                                <img src="{{ $m->media_url }}" class="rounded-md max-w-full mb-1" alt="">
                            @endif
                            @if($m->type === 'video' && $m->media_url)
                                <video src="{{ $m->media_url }}" controls class="rounded-md max-w-full mb-1"></video>
                            @endif
                            @if($m->type === 'audio' && $m->media_url)
                                <audio src="{{ $m->media_url }}" controls class="w-full mb-1"></audio>
                            @endif
                            @if($m->type === 'document' && $m->media_url)
                                <a href="{{ $m->media_url }}" target="_blank" class="flex items-center gap-1 text-emerald-700 underline text-xs mb-1">📄 Download file</a>
                            @endif
                            @if($m->type === 'location')
                                <a href="https://maps.google.com/?q={{ urlencode($m->body ?? '') }}" target="_blank" class="flex items-center gap-1 text-emerald-700 underline text-xs mb-1">📍 View on map</a>
                            @endif
                            @if($m->type === 'sticker' && $m->media_url)
                                <img src="{{ $m->media_url }}" class="w-24 h-24 mb-1" alt="">
                            @endif
                            @if($m->body)
                                <p class="whitespace-pre-wrap break-words leading-relaxed">{{ $m->body }}</p>
                            @endif
                            <div class="flex items-center justify-end gap-1 mt-0.5">
                                <span class="text-[10px] text-slate-500">{{ $m->sent_at?->format('H:i') }}</span>
                                @if($m->direction === 'outbound')
                                    <span class="text-[10px] text-slate-500">{{ $m->status === 'read' ? '✓✓' : ($m->status === 'delivered' ? '✓✓' : '✓') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
                @if($selected->messages->isEmpty())
                    <div class="flex items-center justify-center h-full text-slate-400 text-sm">No messages yet. Start the conversation.</div>
                @endif
            </div>

            <!-- Composer -->
            <div class="bg-[#f0f2f5] px-4 py-3 border-t border-slate-200">
                <form method="POST" action="{{ route('conversations.send', $selected) }}" enctype="multipart/form-data" class="flex items-end gap-2">
                    @csrf
                    <input type="hidden" name="type" value="text" id="msgType">
                    <div class="flex gap-1 mb-1">
                        @foreach(['text','image','video','audio','document','location','sticker'] as $t)
                            <button type="button" onclick="document.getElementById('msgType').value='{{ $t }}'; document.querySelectorAll('.type-btn').forEach(b=>b.classList.remove('bg-emerald-600','text-white')); this.classList.add('bg-emerald-600','text-white');" class="type-btn px-2 py-1 rounded-md text-[10px] font-medium transition {{ $t === 'text' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100' }}">{{ ucfirst($t) }}</button>
                        @endforeach
                    </div>
                    <div class="flex-1 flex items-end gap-2 bg-white rounded-2xl px-3 py-2 shadow-sm">
                        <label class="cursor-pointer p-1.5 rounded-full hover:bg-slate-100 transition" title="Attach file">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <input type="file" name="media_file" class="hidden">
                        </label>
                        <textarea name="body" rows="1" placeholder="Type a message" class="flex-1 bg-transparent outline-none resize-none text-sm max-h-24"></textarea>
                        <input name="caption" type="text" placeholder="Caption" class="hidden">
                        <input name="latitude" type="hidden">
                        <input name="longitude" type="hidden">
                        <input name="location_name" type="hidden">
                        <button type="submit" class="p-2 rounded-full bg-emerald-500 text-white hover:bg-emerald-600 transition shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="flex-1 flex items-center justify-center bg-[#f0f2f5]">
                <div class="text-center">
                    <div class="w-24 h-24 mx-auto rounded-full bg-slate-200 flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h3 class="text-xl font-light text-slate-600">WhatsApp Web Clone</h3>
                    <p class="text-sm text-slate-400 mt-2 max-w-xs mx-auto">Select a conversation from the list to start messaging. Your messages are end-to-end... just kidding, this is Kapso.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection