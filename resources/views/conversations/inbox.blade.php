@extends('layouts.app')
@section('title', 'Inbox')

@section('content')
<style>
    /* WhatsApp Web style — self-contained, no Tailwind rebuild required. (compact) */
    .wai-shell {
        display: flex;
        height: 100vh;
        min-height: 360px;
        background: #fff;
        border: 0;
        border-radius: 0;
        overflow: hidden;
        box-shadow: none;
    }

    /* ---------------- Left: chat list ---------------- */
    .wai-side { width: 280px; min-width: 280px; display: flex; flex-direction: column; background: #fff; border-right: 1px solid #d1d7db; }
    .wai-side-head { height: 42px; display: flex; align-items: center; gap: 6px; padding: 0 10px; background: #f0f2f5; border-bottom: 1px solid #d1d7db; }
    .wai-side-head .title { flex: 1; font-size: 14px; font-weight: 700; color: #111b21; }
    .wai-head-actions { display: flex; align-items: center; gap: 6px; color: #54656f; }
    .wai-head-actions button { background: transparent; border: 0; padding: 3px; color: #54656f; cursor: pointer; display: flex; }
    .wai-me { width: 28px; height: 28px; min-width: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 12px; background: linear-gradient(135deg, #34d399, #0d9488); }

    .wai-search-wrap { background: #fff; padding: 4px 8px; border-bottom: 1px solid #e9edef; }
    .wai-search { position: relative; }
    .wai-search svg { position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #54656f; width: 14px; height: 14px; }
    .wai-search input { width: 100%; height: 26px; border: 0; outline: 0; background: #f0f2f5; border-radius: 6px; padding: 0 10px 0 30px; font-size: 12px; color: #111b21; }
    .wai-search input::placeholder { color: #667781; }

    .wai-filters { display: flex; gap: 4px; padding: 4px 8px; background: #fff; border-bottom: 1px solid #e9edef; }
    .wai-pill { font-size: 10.5px; font-weight: 500; color: #54656f; background: #f0f2f5; border: 0; border-radius: 999px; padding: 2px 8px; cursor: pointer; }
    .wai-pill.on { background: #e7fce3; color: #1f7a45; }

    .wai-chatlist { flex: 1; overflow-y: auto; background: #fff; }
    .wai-item { display: flex; align-items: center; gap: 8px; padding: 5px 10px; text-decoration: none; color: inherit; cursor: pointer; border-bottom: 1px solid #f0f2f5; }
    .wai-item:hover { background: #f5f6f6; }
    .wai-item.is-active { background: #f0f2f5; }
    .wai-avatar { width: 34px; height: 34px; min-width: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 13px; background: linear-gradient(135deg, #34d399, #0d9488); }
    .wai-avatar.sm { width: 28px; height: 28px; min-width: 28px; font-size: 11px; }
    .wai-item-main { flex: 1; min-width: 0; }
    .wai-item-row { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
    .wai-name { font-size: 12.5px; font-weight: 500; color: #111b21; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wai-time { font-size: 10px; color: #667781; white-space: nowrap; }
    .wai-time.unread { color: #25d366; font-weight: 600; }
    .wai-preview { font-size: 11px; color: #667781; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 0; }
    .wai-badge { min-width: 16px; height: 16px; padding: 0 4px; border-radius: 999px; background: #25d366; color: #fff; font-size: 9px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
    .wai-empty-list { padding: 20px 16px; text-align: center; color: #667781; font-size: 12px; line-height: 1.5; }

    /* ---------------- Right: conversation ---------------- */
    .wai-chat { flex: 1; min-width: 0; display: flex; flex-direction: column; background: #efeae2; position: relative; }
    .wai-chat-top { height: 42px; display: flex; align-items: center; gap: 8px; padding: 0 10px; background: #f0f2f5; border-bottom: 1px solid #d1d7db; position: relative; z-index: 2; }
    .wai-back { display: none; background: transparent; border: 0; padding: 3px; cursor: pointer; color: #54656f; }
    .wai-hamburger { display: flex; align-items: center; justify-content: center; background: transparent; border: 0; padding: 3px; cursor: pointer; color: #54656f; border-radius: 6px; }
    .wai-hamburger:hover { background: #e2e5e8; }
    @media (min-width: 640px) {
        .wai-shell.wai-sidebar-collapsed .wai-side { display: none; }
    }
    .wai-chat-top .who { flex: 1; min-width: 0; }
    .wai-chat-top .who .n { font-size: 13.5px; font-weight: 600; color: #111b21; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wai-chat-top .who .p { font-size: 10.5px; color: #667781; margin-top: 1px; }
    .wai-status { font-size: 9.5px; font-weight: 600; padding: 3px 8px; border-radius: 999px; text-transform: capitalize; }
    .wai-status.active, .wai-status.open { background: #d9fdd3; color: #1f7a45; }
    .wai-status.ended { background: #e9edef; color: #54656f; }

    .wai-messages {
        flex: 1; overflow-y: auto; padding: 10px 4% 6px;
        display: flex; flex-direction: column; gap: 2px;
        background-color: #efeae2;
        background-image:
            radial-gradient(rgba(120, 100, 80, .075) 1px, transparent 1px),
            radial-gradient(rgba(120, 100, 80, .045) 1px, transparent 1px);
        background-size: 34px 34px, 17px 17px;
        background-position: 0 0, 8px 9px;
    }
    .wai-date { display: flex; justify-content: center; margin: 8px 0 6px; }
    .wai-date span { background: #fff; color: #54656f; font-size: 11px; font-weight: 500; padding: 3px 9px; border-radius: 6px; box-shadow: 0 1px .5px rgba(11,20,26,.13); }

    .wai-row { display: flex; margin-bottom: 2px; }
    .wai-row.out { justify-content: flex-end; }
    .wai-row.in { justify-content: flex-start; }
    .wai-bubble {
        position: relative; max-width: 65%; padding: 4px 8px 5px; border-radius: 7px;
        font-size: 13px; line-height: 1.35; color: #111b21;
        box-shadow: 0 1px .5px rgba(11,20,26,.13); word-wrap: break-word;
    }
    .wai-bubble.out { background: #d9fdd3; border-top-right-radius: 0; }
    .wai-bubble.in { background: #fff; border-top-left-radius: 0; }
    .wai-bubble.out::after {
        content: ""; position: absolute; right: -6px; top: 0; width: 0; height: 0;
        border-top: 7px solid #d9fdd3; border-right: 6px solid transparent;
    }
    .wai-bubble.in::before {
        content: ""; position: absolute; left: -6px; top: 0; width: 0; height: 0;
        border-top: 7px solid #fff; border-left: 6px solid transparent;
    }
    .wai-bubble img.media, .wai-bubble video { border-radius: 5px; max-width: 100%; max-height: 240px; margin-bottom: 3px; display: block; }
    .wai-bubble audio { width: 100%; margin-bottom: 3px; }
    .wai-bubble .cap { white-space: pre-wrap; }
    .wai-meta { display: flex; align-items: center; justify-content: flex-end; gap: 3px; margin-top: 1px; }
    .wai-meta span { font-size: 9.5px; color: #667781; }
    .wai-meta span.tick { color: #53bdeb; letter-spacing: -2px; }
    .wai-file { display: flex; align-items: center; gap: 6px; padding: 6px 8px; margin-bottom: 3px; background: rgba(255,255,255,.6); border-radius: 5px; text-decoration: none; color: #027eb5; font-size: 11.5px; font-weight: 500; }
    .wai-loc { display: flex; align-items: center; gap: 5px; color: #027eb5; font-weight: 500; font-size: 11.5px; text-decoration: none; margin-bottom: 2px; }
    .wai-sticker { width: 80px; height: 80px; object-fit: contain; margin-bottom: 2px; }

    .wai-composer { background: #f0f2f5; padding: 6px 10px; display: flex; flex-direction: column; }
    .wai-composer form { display: flex; align-items: flex-end; gap: 6px; }
    .wai-composer .icon-btn { width: 32px; height: 32px; min-width: 32px; border: 0; background: transparent; color: #54656f; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
    .wai-composer .icon-btn:hover { background: #e2e5e8; }
    .wai-input-wrap { flex: 1; display: flex; align-items: flex-end; gap: 6px; background: #fff; border-radius: 7px; padding: 4px 8px; min-height: 34px; }
    .wai-input-wrap textarea { flex: 1; border: 0; outline: 0; resize: none; font: inherit; font-size: 13px; max-height: 100px; padding: 4px 0; background: transparent; color: #111b21; }
    .wai-send { width: 32px; height: 32px; min-width: 32px; border-radius: 50%; border: 0; background: #00a884; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .wai-send:hover { background: #008f72; }
    .wai-hidden { display: none !important; }
    .wai-locbox { display: flex; gap: 5px; padding: 0 0 6px 34px; }
    .wai-locbox input { flex: 1; border: 1px solid #d1d7db; border-radius: 6px; padding: 5px 8px; font-size: 11.5px; outline: none; min-width: 0; }
    .wai-attach-chip { display: none; align-items: center; gap: 5px; font-size: 11px; color: #1f7a45; font-weight: 500; background: #e7fce3; border-radius: 5px; padding: 3px 7px; margin: 0 0 6px 34px; width: fit-content; }
    .wai-attach-chip.show { display: flex; }

    .wai-placeholder { flex: 1; display: flex; align-items: center; justify-content: center; background: #f0f2f5; border-bottom: 6px solid #25d366; }
    .wai-placeholder-inner { text-align: center; max-width: 400px; padding: 16px; }
    .wai-placeholder-inner .ic { width: 80px; height: 80px; border-radius: 50%; background: #d1d7db; color: #fff; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
    .wai-placeholder-inner h3 { font-size: 24px; font-weight: 300; color: #41525d; margin: 0 0 8px; letter-spacing: -.3px; }
    .wai-placeholder-inner p { font-size: 12.5px; color: #667781; margin: 0; line-height: 1.5; }
    .wai-chat:not(.wai-no-chat) .wai-placeholder { display: none; }
    .wai-chat.wai-no-chat .wai-chat-top,
    .wai-chat.wai-no-chat .wai-messages,
    .wai-chat.wai-no-chat .wai-composer { display: none; }

    /* Media preview + download */
    .wai-media-wrap { position: relative; display: inline-block; }
    .wai-dl { position: absolute; top: 6px; right: 6px; background: rgba(0,0,0,.55); color: #fff; border: 0; border-radius: 50%; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; font-size: 13px; line-height: 1; }
    .wai-dl:hover { background: rgba(0,0,0,.78); }
    .wai-dl-inline { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; background: rgba(0,0,0,.12); color: #111b21; text-decoration: none; font-size: 13px; flex-shrink: 0; }
    .wai-dl-inline:hover { background: rgba(0,0,0,.2); }
    .wai-lightbox { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,.88); display: none; align-items: center; justify-content: center; flex-direction: column; gap: 14px; padding: 20px; }
    .wai-lightbox.show { display: flex; }
    .wai-lightbox img, .wai-lightbox video { max-width: 92vw; max-height: 78vh; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,.5); }
    .wai-lightbox .lb-actions { display: flex; gap: 10px; }
    .wai-lightbox .lb-btn { background: #fff; color: #111; border: 0; border-radius: 8px; padding: 8px 16px; cursor: pointer; font-size: 13px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .wai-lightbox .lb-btn:hover { background: #f0f2f5; }

    .wai-pdf { width: 260px; height: 200px; border: 0; border-radius: 6px; background: #fff; }
    .wai-pdf-fallback { width: 260px; height: 200px; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 8px; background: #f0f2f5; border-radius: 6px; color: #54656f; font-size: 12.5px; text-align: center; padding: 12px; }
    .wai-voice { position: relative; display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,.5); border-radius: 8px; padding: 6px 8px; min-width: 200px; }
    .wai-voice-ic { font-size: 16px; flex-shrink: 0; }
    .wai-voice audio { flex: 1; width: 100%; min-width: 160px; height: 32px; }

    @media (max-width: 1023px) and (min-width: 640px) {
        .wai-shell { height: calc(100vh - 12.5rem); }
        .wai-chat-top .wai-head-actions button:first-child { display: none; }
        .wai-chat-top .wai-status { display: none; }
    }

    @media (max-width: 639px) {
        .wai-shell { height: calc(100vh - 12.5rem); border-radius: 6px; }
        .wai-side { width: 100%; min-width: 100%; border-right: 0; }
        .wai-back { display: flex; }
        .wai-shell:not(.wai-show-chat) .wai-chat { display: none; }
        .wai-shell.wai-show-chat .wai-side { display: none; }
        .wai-bubble { max-width: 82%; }
        .wai-messages { padding-left: 10px; padding-right: 10px; }
    }
</style>

@php
    $isMobileChat = request()->filled('c');
    $selectedName = $selected ? ($selected->contact->name ?? $selected->contact->phone) : '';
@endphp

<div class="wai-shell {{ $isMobileChat ? 'wai-show-chat' : '' }}" id="waiShell">
    {{-- Left: chat list --}}
    <aside class="wai-side" id="waiList">
        <div class="wai-side-head">
            <button type="button" class="wai-hamburger" id="waiHamburgerSide" title="Toggle chat list" style="color:#54656f;">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="wai-me">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
            <div class="title">Chats</div>
            <div class="wai-head-actions">
                <button type="button" title="New chat">
                    <svg width="21" height="21" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14m-7-7h14"/></svg>
                </button>
                <button type="button" title="Menu">
                    <svg width="21" height="21" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5h.01M12 12h.01M12 19h.01"/></svg>
                </button>
            </div>
        </div>

        <div class="wai-search-wrap">
            <div class="wai-search">
                <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" id="waiSearch" placeholder="Search or start new chat" autocomplete="off">
            </div>
        </div>

        <div class="wai-filters">
            <button type="button" class="wai-pill on">All</button>
            <button type="button" class="wai-pill">Unread</button>
            <button type="button" class="wai-pill">Groups</button>
        </div>

        <div class="wai-chatlist" id="waiChatList">
            @forelse($conversations as $conv)
                @php
                    $contactName = $conv->contact->name ?? $conv->contact->phone;
                    $preview = $conv->latestMessage->body ?? 'No messages yet';
                @endphp
                <a href="{{ route('conversations.index', ['c' => $conv->id]) }}"
                   class="wai-item {{ $selected && $selected->id === $conv->id ? 'is-active' : '' }}"
                   data-id="{{ $conv->id }}" data-search="{{ strtolower($contactName . ' ' . $conv->contact->phone . ' ' . $preview) }}">
                    <div class="wai-avatar">{{ strtoupper(substr($contactName, 0, 1)) }}</div>
                    <div class="wai-item-main">
                        <div class="wai-item-row">
                            <span class="wai-name">{{ $contactName }}</span>
                            <span class="wai-time {{ $conv->unread_count > 0 ? 'unread' : '' }}">{{ $conv->last_message_at?->format('H:i') }}</span>
                        </div>
                        <div class="wai-item-row">
                            <span class="wai-preview">{{ $preview }}</span>
                            @if($conv->unread_count > 0)
                                <span class="wai-badge">{{ $conv->unread_count }}</span>
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="wai-empty-list">No conversations yet.<br>Incoming messages will appear here.</div>
            @endforelse
        </div>
    </aside>

    {{-- Right: conversation --}}
    <section class="wai-chat {{ $selected ? '' : 'wai-no-chat' }}" id="waiChat">
        <div class="wai-chat-top">
            <button type="button" class="wai-hamburger" id="waiHamburger" title="Toggle chat list">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <button type="button" class="wai-back" id="waiBack" title="Back to chats">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="wai-avatar sm" id="waiHeadAvatar">{{ strtoupper(substr($selectedName, 0, 1)) }}</div>
            <div class="who">
                <div class="n" id="waiHeadName">{{ $selectedName }}</div>
                <div class="p" id="waiHeadPhone">{{ $selected->contact->phone ?? '' }}</div>
            </div>
            <span class="wai-status {{ $selected->status ?? 'open' }}" id="waiHeadStatus">{{ $selected->status ?? 'open' }}</span>
            <select id="waiAiMode" class="text-xs border border-slate-300 rounded-md px-2 py-1 bg-white" title="AI mode">
                <option value="off">AI Off</option>
                <option value="assist">AI Assist</option>
                <option value="auto">AI Auto</option>
            </select>
            <span id="waiHandoffBadge" class="hidden text-xs font-semibold px-2 py-1 rounded-full bg-red-100 text-red-700">Handoff</span>
            <div class="wai-head-actions">
                <button type="button" title="Search in chat">
                    <svg width="19" height="19" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
                <button type="button" title="Menu">
                    <svg width="19" height="19" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5h.01M12 12h.01M12 19h.01"/></svg>
                </button>
            </div>
        </div>

        <div class="wai-messages" id="waiMessages" data-conversation="{{ $selected->id ?? '' }}" data-last-date="{{ $selected?->messages->last()?->sent_at?->toDateString() }}">
            @if($selected)
                @include('conversations.partials.messages', ['messages' => $selected->messages])
            @endif
        </div>

        <div id="waiAiPanel" class="px-3 py-2 bg-indigo-50 border-t border-indigo-100 text-xs space-y-2">
            <div class="flex flex-wrap gap-1" id="waiTags"></div>
            <div id="waiSuggestionWrap" class="hidden">
                <div class="text-indigo-700 font-semibold mb-1">AI suggested reply:</div>
                <div id="waiSuggestion" class="bg-white border border-indigo-200 rounded-lg px-3 py-2 text-slate-800 whitespace-pre-wrap"></div>
                <div class="flex gap-2 mt-2">
                    <button type="button" id="waiSendSuggestion" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">Send suggestion</button>
                    <button type="button" id="waiDismissSuggestion" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition">Dismiss</button>
                </div>
            </div>
            <div id="waiHandoffReason" class="hidden text-red-700"></div>
        </div>

        <div class="wai-composer">
            <div class="wai-attach-chip" id="waiAttachChip">
                <span id="waiAttachName">File attached</span>
                <button type="button" id="waiAttachClear" style="border:0;background:transparent;color:#c0392b;cursor:pointer;font-weight:700;">&times;</button>
            </div>
            <div class="wai-locbox wai-hidden" id="waiLocBox">
                <input type="text" id="waiLat" placeholder="Latitude">
                <input type="text" id="waiLng" placeholder="Longitude">
            </div>
            <form method="POST" action="{{ $selected ? route('conversations.send', $selected) : '#' }}" enctype="multipart/form-data" id="waiForm">
                @csrf
                <input type="hidden" name="type" id="waiType" value="text">
                <input type="hidden" name="latitude" id="waiLatHidden">
                <input type="hidden" name="longitude" id="waiLngHidden">
                <input type="file" name="media_file" id="waiFile" class="wai-hidden">
                <button type="button" class="icon-btn" id="waiAttachBtn" title="Attach">
                    <svg width="23" height="23" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.17 7l-6.58 6.59a2 2 0 102.83 2.83l6.41-6.59a4 4 0 00-5.65-5.65L5.76 10.6a6 6 0 108.49 8.48L20.5 13"/></svg>
                </button>
                <button type="button" class="icon-btn" id="waiLocBtn" title="Location">
                    <svg width="21" height="21" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.66 16.66L13.41 20.9a2 2 0 01-2.83 0l-4.24-4.24a8 8 0 1111.32 0z"/><circle cx="12" cy="11" r="3"/></svg>
                </button>
                <div class="wai-input-wrap">
                    <textarea name="body" id="waiBody" rows="1" placeholder="Type a message"></textarea>
                </div>
                <button type="submit" class="wai-send" title="Send">
                    <svg width="21" height="21" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </form>
        </div>

        <div class="wai-placeholder">
            <div class="wai-placeholder-inner">
                <div class="ic">
                    <svg width="52" height="52" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.3" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
                <h3>WhatsApp Web</h3>
                <p>Send and receive messages without keeping your phone online.<br>Select a chat on the left to get started.</p>
            </div>
        </div>
    </section>
</div>

{{-- Lightbox preview --}}
<div class="wai-lightbox" id="waiLightbox" onclick="if(event.target===this) closePreview();">
    <div id="waiLbContent"></div>
    <div class="lb-actions">
        <a id="waiLbDownload" class="lb-btn" download>⬇ Download</a>
        <button type="button" class="lb-btn" onclick="closePreview()">✕ Close</button>
    </div>
</div>

<script>
(function () {
    const shell = document.getElementById('waiShell');
    const chat = document.getElementById('waiChat');
    const list = document.getElementById('waiChatList');
    const base = @json(url('/conversations'));
    let pollTimer = null;

    function esc(s) {
        return (s == null ? '' : String(s)).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function dateChip(label) {
        return '<div class="wai-date"><span>' + esc(label) + '</span></div>';
    }

    function renderMessage(m, box) {
        const lastDate = box.getAttribute('data-last-date') || '';
        let html = '';
        if (m.date_label && m.date_label !== lastDate) {
            html += dateChip(m.date_label);
            box.setAttribute('data-last-date', m.date_label);
        }
        let inner = '';
        if (m.type === 'image' && m.media_url) inner += '<div class="wai-media-wrap"><img class="media" src="' + esc(m.media_url) + '" alt="" onclick="openPreview(\'' + esc(m.media_url) + '\',\'image\')" style="cursor:zoom-in;"><a class="wai-dl" href="/media/download?url=' + encodeURIComponent(m.media_url) + '" download title="Download">⬇</a></div>';
        if (m.type === 'video' && m.media_url) inner += '<div class="wai-media-wrap"><video src="' + esc(m.media_url) + '" controls onclick="openPreview(\'' + esc(m.media_url) + '\',\'video\')" style="cursor:zoom-in;"></video><a class="wai-dl" href="/media/download?url=' + encodeURIComponent(m.media_url) + '" download title="Download">⬇</a></div>';
        if (m.type === 'audio' && m.media_url) inner += '<div class="wai-voice"><span class="wai-voice-ic">🎤</span><audio src="' + esc(m.media_url) + '" controls></audio><a class="wai-dl-inline" href="/media/download?url=' + encodeURIComponent(m.media_url) + '" download title="Download">⬇</a></div>';
        if (m.type === 'sticker' && m.media_url) inner += '<div class="wai-media-wrap"><img class="wai-sticker" src="' + esc(m.media_url) + '" alt="" onclick="openPreview(\'' + esc(m.media_url) + '\',\'image\')" style="cursor:zoom-in;"><a class="wai-dl" href="/media/download?url=' + encodeURIComponent(m.media_url) + '" download title="Download">⬇</a></div>';
        var isPdf = /\\.pdf(\\?|$)/i.test(m.media_url || '');
        if (m.type === 'document' && m.media_url) {
            if (isPdf) inner += '<div class="wai-media-wrap"><iframe src="' + esc(m.media_url) + '" class="wai-pdf" loading="lazy"></iframe><a class="wai-dl" href="/media/download?url=' + encodeURIComponent(m.media_url) + '" download title="Download">⬇</a></div>';
            else inner += '<a class="wai-file" href="/media/download?url=' + encodeURIComponent(m.media_url) + '" download>📄 Download file</a>';
        }
        if (m.type === 'location') inner += '<a class="wai-loc" href="https://maps.google.com/?q=' + encodeURIComponent(m.body || '') + '" target="_blank">📍 View on map</a>';
        var isMedia = ['image','video','audio','sticker','document'].indexOf(m.type) !== -1;
        var b = m.body || '';
        var bodyIsDesc = isMedia && (b.indexOf('http') !== -1 || b.toLowerCase().indexOf('attached') !== -1);
        if (m.body && m.type !== 'location' && !bodyIsDesc) inner += '<div class="cap">' + esc(m.body) + '</div>';
        const tick = m.direction === 'outbound' ? '<span class="tick">' + (m.status === 'read' || m.status === 'delivered' ? '✓✓' : '✓') + '</span>' : '';
        inner += '<div class="wai-meta"><span>' + esc(m.time || '') + '</span>' + tick + '</div>';
        html += '<div class="wai-row ' + (m.direction === 'outbound' ? 'out' : 'in') + '" data-mid="' + esc(m.id) + '">' +
                '<div class="wai-bubble ' + (m.direction === 'outbound' ? 'out' : 'in') + '">' + inner + '</div></div>';
        return html;
    }

    function maxMessageId(box) {
        let max = 0;
        box.querySelectorAll('[data-mid]').forEach(el => { max = Math.max(max, parseInt(el.getAttribute('data-mid'), 10) || 0); });
        return max;
    }

    function initPoll(conversationId) {
        if (pollTimer) clearInterval(pollTimer);
        if (!conversationId) return;
        pollTimer = setInterval(async () => {
            const box = document.getElementById('waiMessages');
            if (!box || box.getAttribute('data-conversation') !== String(conversationId)) return;
            try {
                const res = await fetch(base + '/' + conversationId + '/poll?after_id=' + maxMessageId(box), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.messages && data.messages.length) {
                    const atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 100;
                    data.messages.forEach(m => box.insertAdjacentHTML('beforeend', renderMessage(m, box)));
                    if (atBottom) box.scrollTop = box.scrollHeight;
                }
            } catch (err) { /* transient */ }
        }, 5000);
    }

    function openConversation(id, pushUrl) {
        fetch(base + '/' + id + '/data', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                chat.classList.remove('wai-no-chat');
                shell.classList.add('wai-show-chat');
                document.getElementById('waiHeadAvatar').textContent = data.contact.initial;
                document.getElementById('waiHeadName').textContent = data.contact.name;
                document.getElementById('waiHeadPhone').textContent = data.contact.phone;
                const st = document.getElementById('waiHeadStatus');
                st.textContent = data.status;
                st.className = 'wai-status ' + data.status;
                const box = document.getElementById('waiMessages');
                box.setAttribute('data-conversation', data.id);
                box.setAttribute('data-last-date', '');
                box.innerHTML = data.messages.length ? data.messages.map(m => renderMessage(m, box)).join('') : '<div class="wai-empty-list">No messages yet. Start the conversation.</div>';
                document.getElementById('waiForm').action = base + '/' + data.id + '/messages';
                const modeSel = document.getElementById('waiAiMode');
                if (modeSel) modeSel.value = data.ai_mode || 'off';
                const tagsEl = document.getElementById('waiTags');
                if (tagsEl) {
                    tagsEl.innerHTML = (data.ai_tags || []).map(t => '<span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">' + esc(t) + '</span>').join('');
                }
                const badge = document.getElementById('waiHandoffBadge');
                if (badge) {
                    if (data.ai_handoff) { badge.classList.remove('hidden'); } else { badge.classList.add('hidden'); }
                }
                const reason = document.getElementById('waiHandoffReason');
                if (reason) {
                    if (data.handoff_reason) { reason.textContent = 'Handoff: ' + data.handoff_reason; reason.classList.remove('hidden'); } else { reason.classList.add('hidden'); }
                }
                const sugWrap = document.getElementById('waiSuggestionWrap');
                const sug = document.getElementById('waiSuggestion');
                if (sugWrap && sug) {
                    if (data.ai_suggested_reply) {
                        sug.textContent = data.ai_suggested_reply;
                        sugWrap.classList.remove('hidden');
                        sugWrap.dataset.messageId = data.ai_suggested_message_id || '';
                    } else {
                        sugWrap.classList.add('hidden');
                    }
                }
                box.scrollTop = box.scrollHeight;
                if (pushUrl) history.pushState({}, '', pushUrl);
                initPoll(data.id);
            })
            .catch(() => { location.href = base + '?c=' + id; });
    }

    if (list) {
        list.addEventListener('click', function (e) {
            const item = e.target.closest('.wai-item');
            if (!item) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey) return;
            e.preventDefault();
            list.querySelectorAll('.wai-item').forEach(i => i.classList.remove('is-active'));
            item.classList.add('is-active');
            openConversation(item.getAttribute('data-id'), item.getAttribute('href'));
        });
    }

    // Search filter
    const search = document.getElementById('waiSearch');
    if (search && list) {
        search.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            list.querySelectorAll('.wai-item').forEach(item => {
                const hay = item.getAttribute('data-search') || '';
                item.style.display = (!q || hay.includes(q)) ? '' : 'none';
            });
        });
    }

    const back = document.getElementById('waiBack');
    if (back) back.addEventListener('click', () => shell.classList.remove('wai-show-chat'));

    // Desktop sidebar collapse/expand
    function toggleSidebar() {
        if (window.innerWidth < 640) return;
        shell.classList.toggle('wai-sidebar-collapsed');
        try { localStorage.setItem('waiSidebarCollapsed', shell.classList.contains('wai-sidebar-collapsed') ? '1' : '0'); } catch (e) {}
    }
    const hamburger = document.getElementById('waiHamburger');
    if (hamburger) hamburger.addEventListener('click', toggleSidebar);
    const hamburgerSide = document.getElementById('waiHamburgerSide');
    if (hamburgerSide) hamburgerSide.addEventListener('click', toggleSidebar);
    try {
        if (window.innerWidth >= 640 && localStorage.getItem('waiSidebarCollapsed') === '1') shell.classList.add('wai-sidebar-collapsed');
    } catch (e) {}

    // Composer
    const fileInput = document.getElementById('waiFile');
    const typeInput = document.getElementById('waiType');
    const bodyInput = document.getElementById('waiBody');
    const chip = document.getElementById('waiAttachChip');
    const chipName = document.getElementById('waiAttachName');
    const locBox = document.getElementById('waiLocBox');

    function detectType(file) {
        const t = (file.type || '').toLowerCase();
        if (t.startsWith('image/')) return 'image';
        if (t.startsWith('video/')) return 'video';
        if (t.startsWith('audio/')) return 'audio';
        return 'document';
    }

    const attachBtn = document.getElementById('waiAttachBtn');
    if (attachBtn) attachBtn.addEventListener('click', () => fileInput.click());
    if (fileInput) fileInput.addEventListener('change', () => {
        if (fileInput.files && fileInput.files[0]) {
            const f = fileInput.files[0];
            typeInput.value = detectType(f);
            chip.classList.add('show');
            chipName.textContent = f.name;
            bodyInput.placeholder = 'Add a caption (optional)';
        }
    });
    const attachClear = document.getElementById('waiAttachClear');
    if (attachClear) attachClear.addEventListener('click', () => {
        fileInput.value = '';
        chip.classList.remove('show');
        typeInput.value = 'text';
        bodyInput.placeholder = 'Type a message';
    });
    const locBtn = document.getElementById('waiLocBtn');
    if (locBtn) locBtn.addEventListener('click', () => {
        const hidden = locBox.classList.toggle('wai-hidden');
        typeInput.value = hidden ? 'text' : 'location';
    });
    const form = document.getElementById('waiForm');
    if (form) form.addEventListener('submit', () => {
        document.getElementById('waiLatHidden').value = document.getElementById('waiLat').value;
        document.getElementById('waiLngHidden').value = document.getElementById('waiLng').value;
    });

    const messages = document.getElementById('waiMessages');
    if (messages) {
        messages.scrollTop = messages.scrollHeight;
        initPoll(messages.getAttribute('data-conversation'));
    }

    // AI mode toggle
    const modeSel = document.getElementById('waiAiMode');
    if (modeSel) {
        modeSel.addEventListener('change', async () => {
            const convId = document.getElementById('waiMessages')?.getAttribute('data-conversation');
            if (!convId) return;
            await fetch(base + '/' + convId + '/ai-mode', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('#waiForm input[name=_token]')?.value || '') },
                body: JSON.stringify({ mode: modeSel.value })
            });
        });
    }
    // CSRF meta not present; use form token instead via cookie? We'll read from hidden input is not available. Add fallback: Laravel session cookie auth already; but POST needs CSRF. Let's instead send CSRF from a global. We'll inject below.
    const sendSugBtn = document.getElementById('waiSendSuggestion');
    if (sendSugBtn) {
        sendSugBtn.addEventListener('click', async () => {
            const wrap = document.getElementById('waiSuggestionWrap');
            const convId = document.getElementById('waiMessages')?.getAttribute('data-conversation');
            // Need a message id with suggestion; use last inbound message id stored
            let mid = wrap?.dataset?.messageId;
            if (!mid) {
                // find last message id from DOM
                const ids = Array.from(document.querySelectorAll('[data-mid]')).map(e => parseInt(e.getAttribute('data-mid'),10)).filter(Boolean);
                mid = ids.length ? ids[ids.length-1] : null;
            }
            if (!mid) return;
            const res = await fetch('/messages/' + mid + '/send-suggestion', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('input[name=_token]')?.value || '') },
            });
            const data = await res.json();
            if (data.ok && data.message) {
                const box = document.getElementById('waiMessages');
                box.insertAdjacentHTML('beforeend', renderMessage(data.message, box));
                box.scrollTop = box.scrollHeight;
                wrap.classList.add('hidden');
            }
        });
    }
    const dismissBtn = document.getElementById('waiDismissSuggestion');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            document.getElementById('waiSuggestionWrap')?.classList.add('hidden');
        });
    }

    window.openPreview = function (src, type) {
        const lb = document.getElementById('waiLightbox');
        const content = document.getElementById('waiLbContent');
        const dl = document.getElementById('waiLbDownload');
        if (!lb || !content) return;
        if (type === 'video') {
            content.innerHTML = '<video src="' + src.replace(/[<>"']/g, '') + '" controls autoplay></video>';
        } else {
            content.innerHTML = '<img src="' + src.replace(/[<>"']/g, '') + '" alt="">';
        }
        if (dl) dl.href = src;
        lb.classList.add('show');
    };
    window.closePreview = function () {
        const lb = document.getElementById('waiLightbox');
        const content = document.getElementById('waiLbContent');
        if (lb) lb.classList.remove('show');
        if (content) content.innerHTML = '';
    };
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') window.closePreview(); });

    function attachPdfFallback(root) {
        (root || document).querySelectorAll('.wai-pdf').forEach(function (iframe) {
            if (iframe.dataset.fallbackAttached) return;
            iframe.dataset.fallbackAttached = '1';
            var loaded = false;
            iframe.addEventListener('load', function () { loaded = true; });
            setTimeout(function () {
                if (loaded) return;
                var wrap = iframe.parentElement;
                if (!wrap) return;
                iframe.style.display = 'none';
                var fb = document.createElement('div');
                fb.className = 'wai-pdf-fallback';
                fb.innerHTML = 'Preview unavailable.<br><a href="/media/download?url=' + encodeURIComponent(iframe.src) + '" download style="color:#027eb5;font-weight:500;">Download file</a>';
                wrap.appendChild(fb);
            }, 7000);
        });
    }

    var msgBox = document.getElementById('waiMessages');
    if (msgBox) {
        attachPdfFallback(msgBox);
        var obs = new MutationObserver(function () { attachPdfFallback(msgBox); });
        obs.observe(msgBox, { childList: true, subtree: true });
    }

    window.addEventListener('popstate', () => location.reload());
})();
</script>
@endsection
