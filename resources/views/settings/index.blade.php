@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<div class="max-w-2xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Settings</h2>
        <p class="text-sm text-slate-500 mt-1">Configure your Kapso integration</p>
    </div>
    <form method="POST" action="{{ route('settings.update') }}" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
        @csrf
        <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-slate-900">Kapso Integration</h3>
                <p class="text-xs text-slate-500">Connect your WhatsApp number</p>
            </div>
        </div>
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
        <button class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold hover:from-emerald-700 hover:to-teal-700 transition shadow-lg shadow-emerald-500/25">Save Settings</button>
    </form>
</div>
@endsection