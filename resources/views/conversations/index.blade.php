@extends('layouts.app')
@section('title', 'Inbox')
@section('content')
<div class="fade-in">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">Inbox</h1>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @forelse($conversations as $conv)
            <a href="{{ route('conversations.show', $conv) }}" class="flex items-center gap-4 px-6 py-4 border-b border-slate-100 hover:bg-slate-50 transition">
                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white font-semibold">
                    {{ strtoupper(substr($conv->contact->name ?? $conv->contact->phone, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <p class="font-medium text-slate-900 truncate">{{ $conv->contact->name ?? $conv->contact->phone }}</p>
                        <span class="text-xs text-slate-400">{{ $conv->last_message_at?->diffForHumans() }}</span>
                    </div>
                    <p class="text-sm text-slate-500 truncate">{{ $conv->latestMessage->body ?? 'No messages' }}</p>
                </div>
                @if($conv->unread_count > 0)
                    <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500 text-white">{{ $conv->unread_count }}</span>
                @endif
            </a>
        @empty
            <div class="px-6 py-12 text-center text-slate-400">No conversations yet</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $conversations->links() }}</div>
</div>
@endsection