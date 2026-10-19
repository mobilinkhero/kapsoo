@extends('layouts.app')
@section('title', 'Inbox')

@section('content')
<style>
    /* WhatsApp Web style — self-contained, no Tailwind rebuild required. */
    .wai-shell {
        display: flex;
        height: calc(100vh - 8.5rem);
        min-height: 420px;
        background: #fff;
        border: 1px solid #d1d7db;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 10px 30px -20px rgba(15, 23, 42, .35);
    }

    /* ---------------- Left: chat list ---------------- */
    .wai-side { width: 350px; min-width: 350px; display: flex; flex-direction: column; background: #fff; border-right: 1px solid #d1d7db; }
    .wai-side-head { height: 59px; display: flex; align-items: center; gap: 10px; padding: 0 16px; background: #f0f2f5; border-bottom: 1px solid #d1d7db; }
    .wai-side-head .title { flex: 1; font-size: 18px; font-weight: 700; color: #111b21; }
    .wai-head-actions { display: flex; align-items: center; gap: 14px; color: #54656f; }
    .wai-head-actions button { background: transparent; border: 0; padding: 4px; color: #54656f; cursor: pointer; display: flex; }
    .wai-me { width: 38px; height: 38px; min-width: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 15px; background: linear-gradient(135deg, #34d399, #0d9488); }

    .wai-search-wrap { background: #fff; padding: 7px 12px; border-bottom: 1px solid #e9edef; }
    .wai-search { position: relative; }
    .wai-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #54656f; }
    .wai-search input { width: 100%; height: 35px; border: 0; outline: 0; background: #f0f2f5; border-radius: 8px; padding: 0 12px 0 42px; font-size: 13.5px; color: #111b21; }
    .wai-search input::placeholder { color: #667781; }

    .wai-filters { display: flex; gap: 8px; padding: 8px 12px; background: #fff; border-bottom: 1px solid #e9edef; }
    .wai-pill { font-size: 12.5px; font-weight: 500; color: #54656f; background: #f0f2f5; border: 0; border-radius: 999px; padding: 5px 12px; cursor: pointer; }
    .wai-pill.on { background: #e7fce3; color: #1f7a45; }

    .wai-chatlist { flex: 1; overflow-y: auto; background: #fff; }
    .wai-item { display: flex; align-items: center; gap: 13px; padding: 10px 14px; text-decoration: none; color: inherit; cursor: pointer; border-bottom: 1px solid #f0f2f5; }
    .wai-item:hover { background: #f5f6f6; }
    .wai-item.is-active { background: #f0f2f5; }
    .wai-avatar { width: 49px; height: 49px; min-width: 49px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 18px; background: linear-gradient(135deg, #34d399, #0d9488); }
    .wai-avatar.sm { width: 40px; height: 40px; min-width: 40px; font-size: 15px; }
    .wai-item-main { flex: 1; min-width: 0; }
    .wai-item-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .wai-name { font-size: 15px; font-weight: 500; color: #111b21; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wai-time { font-size: 11.5px; color: #667781; white-space: nowrap; }
    .wai-time.unread { color: #25d366; font-weight: 600; }
    .wai-preview { font-size: 13px; color: #667781; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
    .wai-badge { min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px; background: #25d366; color: #fff; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
    .wai-empty-list { padding: 46px 24px; text-align: center; color: #667781; font-size: 13px; line-height: 1.6; }

    /* ---------------- Right: conversation ---------------- */
    .wai-chat { flex: 1; min-width: 0; display: flex; flex-direction: column; background: #efeae2; position: relative; }
    .wai-chat-top { height: 59px; display: flex; align-items: center; gap: 12px; padding: 0 16px; background: #f0f2f5; border-bottom: 1px solid #d1d7db; position: relative; z-index: 2; }
    .wai-back { display: none; background: transparent; border: 0; padding: 4px; cursor: pointer; color: #54656f; }
    .wai-chat-top .who { flex: 1; min-width: 0; }
    .wai-chat-top .who .n { font-size: 15.5px; font-weight: 600; color: #111b21; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .wai-chat-top .who .p { font-size: 11.5px; color: #667781; margin-top: 1px; }
    .wai-status { font-size: 10.5px; font-weight: 600; padding: 4px 10px; border-radius: 999px; text-transform: capitalize; }
    .wai-status.active, .wai-status.open { background: #d9fdd3; color: #1f7a45; }
    .wai-status.ended { background: #e9edef; color: #54656f; }

    .wai-messages {
        flex: 1; overflow-y: auto; padding: 18px 6% 12px;
        display: flex; flex-direction: column; gap: 2px;
        background-color: #efeae2;
        background-image:
            radial-gradient(rgba(120, 100, 80, .075) 1px, transparent 1px),
            radial-gradient(rgba(120, 100, 80, .045) 1px, transparent 1px);
        background-size: 34px 34px, 17px 17px;
        background-position: 0 0, 8px 9px;
    }
    .wai-date { display: flex; justify-content: center; margin: 12px 0 8px; }
    .wai-date span { background: #fff; color: #54656f; font-size: 12px; font-weight: 500; padding: 5px 12px; border-radius: 7.5px; box-shadow: 0 1px .5px rgba(11,20,26,.13); }

    .wai-row { display: flex; margin-bottom: 2px; }
    .wai-row.out { justify-content: flex-end; }
    .wai-row.in { justify-content: flex-start; }
    .wai-bubble {
        position: relative; max-width: 65%; padding: 6px 9px 7px; border-radius: 7.5px;
        font-size: 13.8px; line-height: 1.4; color: #111b21;
        box-shadow: 0 1px .5px rgba(11,20,26,.13); word-wrap: break-word;
    }
    .wai-bubble.out { background: #d9fdd3; border-top-right-radius: 0; }
    .wai-bubble.in { background: #fff; border-top-left-radius: 0; }
    .wai-bubble.out::after {
        content: ""; position: absolute; right: -7px; top: 0; width: 0; height: 0;
        border-top: 9px solid #d9fdd3; border-right: 8px solid transparent;
    }
    .wai-bubble.in::before {
        content: ""; position: absolute; left: -7px; top: 0; width: 0; height: 0;
        border-top: 9px solid #fff; border-left: 8px solid transparent;
    }
    .wai-bubble img.media, .wai-bubble video { border-radius: 6px; max-width: 100%; max-height: 280px; margin-bottom: 4px; display: block; }
    .wai-bubble audio { width: 100%; margin-bottom: 4px; }
    .wai-bubble .cap { white-space: pre-wrap; }
    .wai-meta { display: flex; align-items: center; justify-content: flex-end; gap: 4px; margin-top: 2px; }
    .wai-meta span { font-size: 10.5px; color: #667781; }
    .wai-meta span.tick { color: #53bdeb; letter-spacing: -2px; }
    .wai-file { display: flex; align-items: center; gap: 8px; padding: 8px 9px; margin-bottom: 4px; background: rgba(255,255,255,.6); border-radius: 6px; text-decoration: none; color: #027eb5; font-size: 12.5px; font-weight: 500; }
    .wai-loc { display: flex; align-items: center; gap: 6px; color: #027eb5; font-weight: 500; font-size: 12.5px; text-decoration: none; margin-bottom: 3px; }
    .wai-sticker { width: 96px; height: 96px; object-fit: contain; margin-bottom: 3px; }

    .wai-composer { background: #f0f2f5; padding: 9px 16px; display: flex; flex-direction: column; }
    .wai-composer form { display: flex; align-items: flex-end; gap: 8px; }
    .wai-composer .icon-btn { width: 38px; height: 38px; min-width: 38px; border: 0; background: transparent; color: #54656f; cursor: pointer; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
    .wai-composer .icon-btn:hover { background: #e2e5e8; }
    .wai-input-wrap { flex: 1; display: flex; align-items: flex-end; gap: 8px; background: #fff; border-radius: 8px; padding: 6px 10px; min-height: 42px; }
    .wai-input-wrap textarea { flex: 1; border: 0; outline: 0; resize: none; font: inherit; font-size: 14px; max-height: 120px; padding: 5px 0; background: transparent; color: #111b21; }
    .wai-send { width: 38px; height: 38px; min-width: 38px; border-radius: 50%; border: 0; background: #00a884; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .wai-send:hover { background: #008f72; }
    .wai-hidden { display: none !important; }
    .wai-locbox { display: flex; gap: 6px; padding: 0 0 8px 46px; }
    .wai-locbox input { flex: 1; border: 1px solid #d1d7db; border-radius: 7px; padding: 7px 10px; font-size: 12.5px; outline: none; min-width: 0; }
    .wai-attach-chip { display: none; align-items: center; gap: 6px; font-size: 12px; color: #1f7a45; font-weight: 500; background: #e7fce3; border-radius: 6px; padding: 4px 9px; margin: 0 0 8px 46px; width: fit-content; }
    .wai-attach-chip.show { display: flex; }

    .wai-placeholder { flex: 1; display: flex; align-items: center; justify-content: center; background: #f0f2f5; border-bottom: 6px solid #25d366; }
    .wai-placeholder-inner { text-align: center; max-width: 440px; padding: 24px; }
    .wai-placeholder-inner .ic { width: 100px; height: 100px; border-radius: 50%; background: #d1d7db; color: #fff; display: flex; align-items: center; justify-content: center; margin: 0 auto 22px; }
    .wai-placeholder-inner h3 { font-size: 30px; font-weight: 300; color: #41525d; margin: 0 0 12px; letter-spacing: -.3px; }
    .wai-placeholder-inner p { font-size: 13.5px; color: #667781; margin: 0; line-height: 1.6; }
    .wai-chat:not(.wai-no-chat) .wai-placeholder { display: none; }
    .wai-chat.wai-no-chat .wai-chat-top,
    .wai-chat.wai-no-chat .wai-messages,
    .wai-chat.wai-no-chat .wai-composer { display: none; }

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
        .wai-messages { padding-left: 14px; padding-right: 14px; }
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
            <button type="button" class="wai-back" id="waiBack" title="Back to chats">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="wai-avatar sm" id="waiHeadAvatar">{{ strtoupper(substr($selectedName, 0, 1)) }}</div>
            <div class="who">
                <div class="n" id="waiHeadName">{{ $selectedName }}</div>
                <div class="p" id="waiHeadPhone">{{ $selected->contact->phone ?? '' }}</div>
            </div>
            <span class="wai-status {{ $selected->status ?? 'open' }}" id="waiHeadStatus">{{ $selected->status ?? 'open' }}</span>
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
        if (m.type === 'image' && m.media_url) inner += '<img class="media" src="' + esc(m.media_url) + '" alt="">';
        if (m.type === 'video' && m.media_url) inner += '<video src="' + esc(m.media_url) + '" controls></video>';
        if (m.type === 'audio' && m.media_url) inner += '<audio src="' + esc(m.media_url) + '" controls></audio>';
        if (m.type === 'sticker' && m.media_url) inner += '<img class="wai-sticker" src="' + esc(m.media_url) + '" alt="">';
        if (m.type === 'document' && m.media_url) inner += '<a class="wai-file" href="' + esc(m.media_url) + '" target="_blank">📄 Download file</a>';
        if (m.type === 'location') inner += '<a class="wai-loc" href="https://maps.google.com/?q=' + encodeURIComponent(m.body || '') + '" target="_blank">📍 View on map</a>';
        if (m.body && m.type !== 'location') inner += '<div class="cap">' + esc(m.body) + '</div>';
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

    window.addEventListener('popstate', () => location.reload());
})();
</script>
@endsection
