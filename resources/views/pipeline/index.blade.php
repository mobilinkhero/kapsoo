@extends('layouts.app')
@section('title', 'Lead Pipeline')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Lead Pipeline</h1>
        <div class="text-sm text-slate-600">
            Total: <strong>{{ $total }}</strong> |
            Won: <strong>{{ $won }}</strong> |
            Conversion: <strong>{{ $conversionRate }}%</strong>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        @foreach($stages as $stage)
            <div class="bg-slate-100 rounded-xl p-3 min-h-[400px]">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-slate-700 capitalize">{{ $stage }}</h2>
                    <span class="text-xs bg-white px-2 py-0.5 rounded-full text-slate-600">{{ $counts[$stage] }}</span>
                </div>
                <div class="space-y-2">
                    @foreach($columns[$stage] as $conv)
                        <div class="bg-white rounded-lg p-3 shadow-sm border border-slate-200">
                            <div class="font-medium text-sm text-slate-800">{{ $conv->contact->name ?? $conv->contact->phone }}</div>
                            <div class="text-xs text-slate-500 mt-1">{{ $conv->contact->phone }}</div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">Score: {{ $conv->lead_score ?? 0 }}</span>
                                <a href="{{ route('conversations.index', ['c' => $conv->id]) }}" class="text-xs text-blue-600 hover:underline">Open</a>
                            </div>
                            @if($conv->latestMessage)
                                <div class="text-xs text-slate-400 mt-1 truncate">{{ \Illuminate\Support\Str::limit($conv->latestMessage->body, 40) }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection