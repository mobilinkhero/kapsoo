@extends('layouts.app')
@section('title', 'Bot Test Result')
@section('content')
<div class="max-w-3xl space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-900">Bot Test Result</h2>
        <a href="{{ route('bot-test.index') }}" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">New Test</a>
    </div>

    @if(!empty($error))
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $error }}</div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <h3 class="font-semibold text-slate-900 mb-2">Inbound</h3>
        <p class="text-slate-700">{{ $inbound->body }}</p>
        <div class="mt-2 text-xs text-slate-500">
            Tags: {{ implode(', ', $inbound->ai_tags ?? []) ?: 'none' }} ·
            Confidence: {{ $inbound->ai_confidence ?? 'n/a' }} ·
            Handoff triggered: {{ $inbound->ai_handoff_triggered ? 'yes' : 'no' }}
        </div>
        @if($inbound->ai_suggested_reply)
            <div class="mt-3 p-3 rounded-lg bg-indigo-50 border border-indigo-200 text-sm text-indigo-900 whitespace-pre-wrap">
                <span class="font-semibold">Suggested reply:</span> {{ $inbound->ai_suggested_reply }}
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <h3 class="font-semibold text-slate-900 mb-2">Conversation state</h3>
        <div class="text-sm text-slate-700 space-y-1">
            <div>Contact: {{ $conversation->contact->name ?? $conversation->contact->phone }} ({{ $conversation->contact->phone }})</div>
            <div>AI mode: {{ $conversation->ai_mode }}</div>
            <div>AI handoff: {{ $conversation->ai_handoff ? 'yes' : 'no' }}{{ $conversation->handoff_reason ? ' — ' . $conversation->handoff_reason : '' }}</div>
            <div>Tags: {{ implode(', ', $conversation->ai_tags ?? []) ?: 'none' }}</div>
            <div>Lead stage: {{ $conversation->lead_stage ?? 'n/a' }} · score: {{ $conversation->lead_score ?? 'n/a' }}</div>
            <div>Elapsed: {{ $elapsed }}s</div>
        </div>
    </div>

    @if($outbound)
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <h3 class="font-semibold text-slate-900 mb-2">Bot reply (auto-sent)</h3>
            <p class="text-slate-700 whitespace-pre-wrap">{{ $outbound->body }}</p>
            <div class="mt-2 text-xs text-slate-500">Status: {{ $outbound->status }}</div>
        </div>
    @elseif($conversation->ai_mode === 'auto')
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">No auto-reply was sent (AI may be disabled or handoff triggered).</div>
    @else
        <div class="rounded-xl bg-slate-50 border border-slate-200 text-slate-600 px-4 py-3 text-sm">Assist mode: reply not sent automatically. Use the suggested reply above.</div>
    @endif
</div>
@endsection