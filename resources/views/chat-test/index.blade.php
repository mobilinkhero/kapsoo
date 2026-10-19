@extends('layouts.app')
@section('title', 'Live Chat Test')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Live Chat Test</h2>
            <p class="text-sm text-slate-500 mt-1">Chat with the AI bot in real time. No Kapso needed.</p>
        </div>
        <div class="flex items-center gap-2">
            <select id="modeSel" class="text-sm border border-slate-300 rounded-lg px-3 py-2 bg-white">
                <option value="assist">Assist</option>
                <option value="auto">Auto</option>
                <option value="off">Off</option>
            </select>
            <button id="resetBtn" class="px-3 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-sm hover:bg-slate-50">Reset</button>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col" style="height: 70vh;">
        <div id="chatBox" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50">
            <div class="text-center text-xs text-slate-400">Start chatting — type a message below.</div>
        </div>
        <div id="metaBox" class="px-4 py-2 border-t border-slate-100 text-xs text-slate-500 bg-white"></div>
        <form id="chatForm" class="p-3 border-t border-slate-200 bg-white flex gap-2">
            @csrf
            <input type="text" id="msgInput" placeholder="Type a message..." autocomplete="off" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">Send</button>
        </form>
    </div>
</div>

<script>
(function () {
    const chatBox = document.getElementById('chatBox');
    const metaBox = document.getElementById('metaBox');
    const form = document.getElementById('chatForm');
    const input = document.getElementById('msgInput');
    const modeSel = document.getElementById('modeSel');
    const token = form.querySelector('input[name=_token]').value;
    const sendUrl = '{{ route('chat-test.send') }}';
    const resetUrl = '{{ route('chat-test.reset') }}';

    function bubble(text, dir, extra) {
        const wrap = document.createElement('div');
        wrap.className = 'flex ' + (dir === 'out' ? 'justify-end' : 'justify-start');
        const b = document.createElement('div');
        b.className = 'max-w-[75%] px-3 py-2 rounded-2xl text-sm whitespace-pre-wrap ' + (dir === 'out' ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-200 text-slate-800');
        b.textContent = text;
        wrap.appendChild(b);
        if (extra) {
            const m = document.createElement('div');
            m.className = 'text-[10px] text-slate-400 mt-1 ' + (dir === 'out' ? 'text-right' : '');
            m.textContent = extra;
            wrap.appendChild(m);
        }
        chatBox.appendChild(wrap);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = input.value.trim();
        if (!msg) return;
        bubble(msg, 'in', 'you');
        input.value = '';
        const loading = document.createElement('div');
        loading.className = 'flex justify-start';
        loading.innerHTML = '<div class="px-3 py-2 rounded-2xl bg-white border border-slate-200 text-slate-400 text-sm">thinking...</div>';
        chatBox.appendChild(loading);
        chatBox.scrollTop = chatBox.scrollHeight;

        try {
            const res = await fetch(sendUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ message: msg, mode: modeSel.value })
            });
            const data = await res.json();
            loading.remove();
            if (!data.ok) { bubble('Error: ' + (data.error || 'unknown'), 'out'); return; }

            if (data.inbound.tags && data.inbound.tags.length) {
                const t = document.createElement('div');
                t.className = 'text-[10px] text-indigo-500 ml-1';
                t.textContent = 'tags: ' + data.inbound.tags.join(', ') + (data.inbound.confidence != null ? ' · conf ' + data.inbound.confidence : '');
                chatBox.appendChild(t);
            }
            if (data.inbound.suggested_reply) {
                bubble('💡 ' + data.inbound.suggested_reply, 'out', 'suggested (assist)');
            }
            if (data.outbound) {
                bubble(data.outbound.body, 'out', 'bot · ' + data.outbound.status + ' · ' + data.outbound.time);
            } else if (data.conversation.ai_handoff) {
                bubble('🤝 Handoff: ' + (data.conversation.handoff_reason || 'human takeover'), 'out', 'handoff');
            }

            const c = data.conversation;
            metaBox.textContent = 'mode: ' + c.ai_mode + ' · handoff: ' + (c.ai_handoff ? 'yes' : 'no') + ' · tags: ' + (c.ai_tags.join(', ') || 'none') + ' · lead: ' + (c.lead_stage || 'n/a') + ' (' + (c.lead_score ?? '-') + ') · ' + data.elapsed + 's';
        } catch (err) {
            loading.remove();
            bubble('Network error: ' + err.message, 'out');
        }
    });

    document.getElementById('resetBtn').addEventListener('click', async () => {
        await fetch(resetUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token } });
        chatBox.innerHTML = '<div class="text-center text-xs text-slate-400">Conversation reset.</div>';
        metaBox.textContent = '';
    });
})();
</script>
@endsection