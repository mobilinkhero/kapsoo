@extends('layouts.app')
@section('title', 'Test RAG')
@section('content')
<div class="max-w-3xl space-y-4">
    <h2 class="text-2xl font-bold text-slate-900">Test RAG Retrieval</h2>
    <form method="GET" action="{{ route('knowledge.test') }}" class="flex gap-3">
        <input name="q" value="{{ $query }}" placeholder="Ask a question..." class="flex-1 px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none">
        <button class="px-5 py-3 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">Ask</button>
    </form>

    @if($query)
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <h3 class="font-semibold text-slate-900 mb-2">AI Answer</h3>
            <p class="text-slate-700 whitespace-pre-wrap">{{ $answer ?? 'No answer (OpenAI not configured?)' }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <h3 class="font-semibold text-slate-900 mb-2">Retrieved Chunks ({{ count($chunks) }})</h3>
            @forelse($chunks as $c)
                <div class="mb-3 p-3 rounded-lg bg-slate-50 border border-slate-200 text-sm text-slate-700">{{ $c }}</div>
            @empty
                <p class="text-slate-500 text-sm">No chunks retrieved.</p>
            @endforelse
        </div>
    @endif
</div>
@endsection