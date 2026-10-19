@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<div class="fade-in max-w-2xl">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">Settings</h1>
    <form method="POST" action="{{ route('settings.update') }}" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf
        <h2 class="font-semibold text-slate-900">Kapso Integration</h2>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Base URL</label>
            <input name="kapso_base_url" value="{{ old('kapso_base_url', $settings['kapso_base_url'] ?? 'https://api.kapso.ai') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">API Key</label>
            <input name="kapso_api_key" value="{{ old('kapso_api_key', $settings['kapso_api_key'] ?? '') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Phone Number ID</label>
            <input name="kapso_phone_number_id" value="{{ old('kapso_phone_number_id', $settings['kapso_phone_number_id'] ?? '') }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <p class="text-xs text-slate-400">Webhook URL to configure in Kapso: <code class="bg-slate-100 px-2 py-1 rounded">{{ url('/api/webhooks/kapso') }}</code></p>
        <button class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition">Save</button>
    </form>
</div>
@endsection