@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<div class="max-w-5xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Settings</h2>
        <p class="text-sm text-slate-500 mt-1">Configure your Kapso integration and AI assistant</p>
    </div>

    {{-- status messages are rendered under each card's buttons --}}

    {{-- Kapso card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
        <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-slate-900">Kapso Integration</h3>
                <p class="text-xs text-slate-500">Connect your WhatsApp number</p>
            </div>
        </div>
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="section" value="kapso">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Base URL</label>
                <input name="kapso_base_url" value="{{ old('kapso_base_url', $settings['kapso_base_url'] ?? 'https://api.kapso.ai/meta/whatsapp/v24.0') }}" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">API Key</label>
                <input name="kapso_api_key" value="{{ old('kapso_api_key', $settings['kapso_api_key'] ?? '') }}" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition" placeholder="pk_live_...">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone Number ID</label>
                <input name="kapso_phone_number_id" value="{{ old('kapso_phone_number_id', $settings['kapso_phone_number_id'] ?? '') }}" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>
            <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                <p class="text-xs text-slate-500">Webhook URL to configure in Kapso:</p>
                <code class="text-xs text-emerald-700 font-mono break-all">{{ url('/api/webhooks/kapso') }}</code>
            </div>
            <div class="flex flex-wrap gap-3">
                <button class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-600 text-white font-semibold hover:from-emerald-700 hover:to-emerald-700 transition shadow-lg shadow-emerald-500/25">Save Kapso</button>
                <button type="submit" name="test_mode" value="1" class="px-6 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition shadow-sm">Test Connection</button>
            </div>
            @if(session('saved_section') === 'kapso' && session('success'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('test_section') === 'kapso' && session('test_result'))
                @php $tr = session('test_result'); @endphp
                <div class="rounded-xl px-4 py-3 text-sm {{ ($tr['ok'] ?? false) ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-red-50 border border-red-200 text-red-700' }}">
                    <p class="font-semibold">{{ ($tr['ok'] ?? false) ? 'Connection successful (HTTP ' . ($tr['status'] ?? '?') . ')' : 'Connection failed (HTTP ' . ($tr['status'] ?? '?') . ')' }}</p>
                    <pre class="mt-2 text-xs overflow-x-auto whitespace-pre-wrap bg-white/50 rounded-lg p-3">{{ json_encode($tr, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </form>
    </div>

    {{-- AI card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
        <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-blue-600 flex items-center justify-center text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-slate-900">AI Assistant (OpenAI)</h3>
                <p class="text-xs text-slate-500">Sales & support copilot with knowledge base</p>
            </div>
        </div>
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="section" value="ai">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="ai_enabled" value="1" {{ ($settings['ai_enabled'] ?? '0') === '1' ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                Enable AI processing
            </label>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">OpenAI API Key</label>
                <input name="openai_api_key" value="{{ old('openai_api_key', $settings['openai_api_key'] ?? '') }}" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition" placeholder="sk-...">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Chat Model</label>
                    <input name="openai_model" value="{{ old('openai_model', $settings['openai_model'] ?? 'gpt-4o-mini') }}" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Embedding Model</label>
                    <input name="openai_embedding_model" value="{{ old('openai_embedding_model', $settings['openai_embedding_model'] ?? 'text-embedding-3-small') }}" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Handoff confidence threshold (0-1)</label>
                <input name="ai_handoff_confidence" type="number" step="0.05" min="0" max="1" value="{{ old('ai_handoff_confidence', $settings['ai_handoff_confidence'] ?? '0.5') }}" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">System prompt (sales persona)</label>
                <textarea name="ai_system_prompt" rows="5" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">{{ old('ai_system_prompt', $settings['ai_system_prompt'] ?? '') }}</textarea>
            </div>
            <div class="flex flex-wrap gap-3">
                <button class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-blue-600 text-white font-semibold hover:from-emerald-700 hover:to-blue-700 transition shadow-lg shadow-emerald-500/25">Save AI</button>
                <button type="submit" name="test_mode" value="1" class="px-6 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition shadow-sm">Test Connection</button>
            </div>
            @if(session('saved_section') === 'ai' && session('success'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif
            @if(session('test_section') === 'ai' && session('test_result'))
                @php $tr = session('test_result'); @endphp
                <div class="rounded-xl px-4 py-3 text-sm {{ ($tr['ok'] ?? false) ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-red-50 border border-red-200 text-red-700' }}">
                    <p class="font-semibold">{{ ($tr['ok'] ?? false) ? 'Connection successful (HTTP ' . ($tr['status'] ?? '?') . ')' : 'Connection failed (HTTP ' . ($tr['status'] ?? '?') . ')' }}</p>
                    <pre class="mt-2 text-xs overflow-x-auto whitespace-pre-wrap bg-white/50 rounded-lg p-3">{{ json_encode($tr, JSON_PRETTY_PRINT) }}</pre>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection