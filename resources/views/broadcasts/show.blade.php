@extends('layouts.app')
@section('title', 'Broadcast')
@section('content')
<div class="fade-in">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">{{ $broadcast->name }}</h1>
        @if($broadcast->status === 'draft')
            <form method="POST" action="{{ route('broadcasts.send', $broadcast) }}">@csrf
                <button class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 transition">Send Now</button>
            </form>
        @endif
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm mb-6">
        <p class="text-sm text-slate-500">Template: <span class="font-medium text-slate-900">{{ $broadcast->template->name ?? '—' }}</span></p>
        <p class="text-sm text-slate-500">Status: <span class="font-medium text-slate-900">{{ $broadcast->status }}</span></p>
        <p class="text-sm text-slate-500">Sent: {{ $broadcast->sent_count }} · Failed: {{ $broadcast->failed_count }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-6 py-3 font-medium">Contact</th>
                    <th class="text-left px-6 py-3 font-medium">Status</th>
                    <th class="text-left px-6 py-3 font-medium">Error</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($broadcast->recipients as $r)
                    <tr>
                        <td class="px-6 py-3 text-slate-900">{{ $r->contact->name ?? $r->contact->phone }}</td>
                        <td class="px-6 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-700">{{ $r->status }}</span></td>
                        <td class="px-6 py-3 text-red-500 text-xs">{{ $r->error }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection