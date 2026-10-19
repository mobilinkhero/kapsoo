@extends('layouts.app')
@section('title', 'Integration Guide')
@section('content')
<div class="max-w-4xl space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Kapso Integration Guide</h2>
        <p class="text-sm text-slate-500 mt-1">How to connect your Kapso WhatsApp account and make everything work</p>
    </div>

    <!-- Status -->
    <div class="rounded-2xl border {{ $configured ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} p-5 flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl {{ $configured ? 'bg-emerald-500' : 'bg-amber-500' }} flex items-center justify-center text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $configured ? 'M5 13l4 4L19 7' : 'M12 9v2m0 4h.01M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' }}"/></svg>
        </div>
        <div>
            <p class="font-semibold {{ $configured ? 'text-emerald-800' : 'text-amber-800' }}">{{ $configured ? 'Kapso is configured' : 'Kapso is not configured yet' }}</p>
            <p class="text-sm {{ $configured ? 'text-emerald-600' : 'text-amber-700' }}">{{ $configured ? 'API key and phone number ID are set.' : 'Add your API key and phone number ID in Settings.' }}</p>
        </div>
        <a href="{{ route('settings.index') }}" class="ml-auto px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">Open Settings</a>
    </div>

    <!-- Steps -->
    <div class="space-y-4">
        @php
            $steps = [
                ['Create a Kapso account', 'Go to kapso.com and sign up. Create a project. You get a free pre-verified WhatsApp number to start.'],
                ['Get your API key', 'In the Kapso dashboard, go to Project Settings → API Keys. Copy your project API key.'],
                ['Get your Phone Number ID', 'In Kapso, go to WhatsApp → Phone Numbers. Copy the phone_number_id for the number you want to use.'],
                ['Add credentials in this app', 'Go to Settings in this app. Paste Base URL (https://api.kapso.ai), API Key, and Phone Number ID. Save.'],
                ['Configure the webhook', 'In Kapso dashboard → Webhooks, create a webhook. Set URL to: ' . url('/api/webhooks/kapso') . '. Subscribe to whatsapp.message.received, whatsapp.message.sent, whatsapp.message.delivered, whatsapp.message.read, whatsapp.message.failed, whatsapp.conversation.created, whatsapp.conversation.ended.'],
                ['Test receiving', 'Send a WhatsApp message to your Kapso number. It should appear in the Inbox of this app.'],
                ['Test sending', 'Open a conversation in the Inbox and send a reply. The message should be delivered via Kapso.'],
                ['Create & sync templates', 'Create templates in Kapso (or here). Click "Sync from Kapso" on the Templates page to import approved templates.'],
                ['Run broadcasts', 'Create a broadcast, select a template and recipients, then click Send. Kapso will deliver to all recipients.'],
            ];
        @endphp
        @foreach($steps as $i => [$title, $desc])
            <div class="flex gap-4 bg-white rounded-2xl border border-slate-200 p-5 shadow-sm card-hover">
                <div class="shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-bold text-sm shadow">
                    {{ $i + 1 }}
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                    <p class="text-sm text-slate-600 mt-1 leading-relaxed">{{ $desc }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Webhook URL highlight -->
    <div class="rounded-2xl bg-slate-900 text-white p-6 shadow-xl">
        <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Your Webhook URL</p>
        <code class="block mt-2 text-emerald-400 font-mono text-sm break-all">{{ url('/api/webhooks/kapso') }}</code>
        <p class="text-xs text-slate-400 mt-3">Use this URL in the Kapso dashboard webhook settings. The endpoint accepts POST requests with the X-Webhook-Event header and returns 200 OK.</p>
    </div>

    <!-- How to register webhook -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        <h3 class="font-semibold text-slate-900">How to register the webhook in Kapso</h3>
        <p class="text-sm text-slate-600">Important: message events are sent to <strong>phone-number webhooks</strong>, not project webhooks. Project webhooks only receive connection/workflow events.</p>
        <div class="space-y-2 text-sm text-slate-600">
            <p class="font-medium text-slate-800">Option A — Kapso Dashboard:</p>
            <ol class="list-decimal list-inside space-y-1 ml-2">
                <li>Open your Kapso project → WhatsApp → select your phone number → Edit.</li>
                <li>Find "Webhook destination URL" / "Kapso webhooks".</li>
                <li>Paste your webhook URL above.</li>
                <li>Select events: <code class="bg-slate-100 px-1 rounded">whatsapp.message.received</code>, <code class="bg-slate-100 px-1 rounded">whatsapp.message.sent</code>, <code class="bg-slate-100 px-1 rounded">whatsapp.message.delivered</code>, <code class="bg-slate-100 px-1 rounded">whatsapp.message.read</code>, <code class="bg-slate-100 px-1 rounded">whatsapp.message.failed</code>, <code class="bg-slate-100 px-1 rounded">whatsapp.conversation.created</code>, <code class="bg-slate-100 px-1 rounded">whatsapp.conversation.ended</code>.</li>
                <li>Save. Send a test message to your WhatsApp number — it should appear in the Inbox.</li>
            </ol>
        </div>
        <div class="space-y-2 text-sm text-slate-600">
            <p class="font-medium text-slate-800">Option B — Kapso API (curl):</p>
            <pre class="bg-slate-900 text-emerald-300 text-xs p-4 rounded-xl overflow-x-auto">curl -X POST "https://api.kapso.ai/platform/v1/whatsapp/phone_numbers/YOUR_PHONE_NUMBER_ID/webhooks" \
  -H "X-API-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "whatsapp_webhook": {
      "kind": "kapso",
      "url": "{{ url('/api/webhooks/kapso') }}",
      "events": ["whatsapp.message.received","whatsapp.message.sent","whatsapp.message.delivered","whatsapp.message.read","whatsapp.message.failed","whatsapp.conversation.created","whatsapp.conversation.ended"],
      "secret_key": "your-secret-key"
    }
  }'</pre>
        </div>
        <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            <strong>Why "nothing in return"?</strong> Kapso webhooks only fire when real events happen (e.g., a message is received). The dashboard's test button may just check reachability. Make sure you selected the events above and that your number is connected. Also verify the URL is HTTPS and returns 200 (it does — tested).
        </div>
    </div>

    <!-- How it works -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <h3 class="font-semibold text-slate-900 mb-3">How the integration works</h3>
        <ul class="space-y-2 text-sm text-slate-600">
            <li class="flex gap-2"><span class="text-emerald-500">•</span> <strong>Inbound:</strong> Kapso sends a webhook to this app when a customer messages you. The app stores the contact, conversation, and message, and shows it in the Inbox.</li>
            <li class="flex gap-2"><span class="text-emerald-500">•</span> <strong>Outbound:</strong> When you reply in the Inbox, this app calls the Kapso API to send the message. Kapso delivers it to WhatsApp.</li>
            <li class="flex gap-2"><span class="text-emerald-500">•</span> <strong>Statuses:</strong> Kapso sends delivery/read/failed events, which update message status in the chat.</li>
            <li class="flex gap-2"><span class="text-emerald-500">•</span> <strong>Templates:</strong> Templates are managed in Kapso. Sync them here to use in broadcasts.</li>
            <li class="flex gap-2"><span class="text-emerald-500">•</span> <strong>Broadcasts:</strong> This app loops through recipients and calls Kapso's send-template API for each.</li>
        </ul>
    </div>
</div>
@endsection