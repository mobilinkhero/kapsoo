@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-6">
    <!-- Welcome -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-700 p-8 text-white shadow-xl">
        <div class="absolute -right-10 -top-10 w-48 h-48 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -left-10 -bottom-10 w-48 h-48 rounded-full bg-emerald-400/20 blur-2xl"></div>
        <div class="relative">
            <p class="text-emerald-100 text-sm font-medium">Welcome back</p>
            <h1 class="text-3xl font-bold mt-1">{{ auth()->user()->name ?? 'Admin' }} 👋</h1>
            <p class="text-emerald-100 mt-2 max-w-xl">Here's what's happening with your WhatsApp workspace today.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('conversations.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white text-emerald-700 font-semibold text-sm hover:bg-emerald-50 transition shadow">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    Open Inbox
                </a>
                <a href="{{ route('broadcasts.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500/30 text-white font-semibold text-sm hover:bg-emerald-500/40 transition border border-white/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13.47a4.5 4.5 0 000-2.94M19 18a7.5 7.5 0 000-12"/></svg>
                    New Broadcast
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $cards = [
                ['Contacts', $stats['contacts'], 'from-blue-500 to-indigo-600', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['Conversations', $stats['conversations'], 'from-emerald-500 to-teal-600', 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                ['Messages', $stats['messages'], 'from-violet-500 to-purple-600', 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                ['Unread', $stats['unread'], 'from-amber-500 to-orange-600', 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
            ];
        @endphp
        @foreach($cards as [$label, $value, $grad, $icon])
            <div class="card-hover bg-white rounded-2xl border border-slate-200 p-5 shadow-sm slide-up">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500 font-medium">{{ $label }}</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $value }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $grad }} flex items-center justify-center text-white shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Recent conversations -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900">Recent Conversations</h2>
            <a href="{{ route('conversations.index') }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">View all →</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($recentConversations as $conv)
                <a href="{{ route('conversations.show', $conv) }}" class="flex items-center gap-4 px-6 py-4 hover:bg-slate-50 transition group">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center text-slate-600 font-semibold group-hover:from-emerald-400 group-hover:to-teal-500 group-hover:text-white transition">
                        {{ strtoupper(substr($conv->contact->name ?? $conv->contact->phone, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-900 truncate">{{ $conv->contact->name ?? $conv->contact->phone }}</p>
                        <p class="text-sm text-slate-500 truncate">{{ $conv->latestMessage->body ?? 'No messages' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-xs text-slate-400">{{ $conv->last_message_at?->diffForHumans() }}</p>
                        @if($conv->unread_count > 0)
                            <span class="inline-block mt-1 px-2 py-0.5 text-xs rounded-full bg-emerald-500 text-white font-medium">{{ $conv->unread_count }}</span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="px-6 py-16 text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <p class="text-slate-500 font-medium">No conversations yet</p>
                    <p class="text-sm text-slate-400 mt-1">Messages will appear here once your WhatsApp number receives them.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection