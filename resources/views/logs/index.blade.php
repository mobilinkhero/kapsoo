@extends('layouts.app')
@section('title', 'Live Logs')
@section('content')
<div class="space-y-4" x-data="logViewer()" x-init="start()">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Live Logs</h2>
            <p class="text-sm text-slate-500 mt-1">Real-time Laravel log viewer (auto-refresh every 5s)</p>
        </div>
        <div class="flex gap-2">
            <button @click="toggle()" class="px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 font-medium hover:bg-slate-50 transition shadow-sm">
                <span x-text="paused ? 'Resume' : 'Pause'"></span>
            </button>
            <button @click="clear()" class="px-4 py-2.5 rounded-xl bg-red-50 border border-red-200 text-red-600 font-medium hover:bg-red-100 transition shadow-sm">Clear Logs</button>
        </div>
    </div>

    <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <div class="px-4 py-2 border-b border-slate-800 flex items-center justify-between text-xs text-slate-400">
            <span>storage/logs/laravel.log</span>
            <span x-text="lines.length + ' lines'"></span>
        </div>
        <div class="h-[60vh] overflow-y-auto scrollbar-thin p-4 font-mono text-xs leading-relaxed" x-ref="box">
            <template x-for="(line, idx) in lines" :key="idx">
                <div class="whitespace-pre-wrap break-all py-0.5" :class="lineClass(line)" x-text="line"></div>
            </template>
            <div x-show="lines.length === 0" class="text-slate-500 text-center py-12">No log entries yet.</div>
        </div>
    </div>
</div>

<script>
function logViewer() {
    return {
        lines: [],
        paused: false,
        timer: null,
        async fetchLogs() {
            if (this.paused) return;
            try {
                const res = await fetch('{{ route('logs.data') }}?lines=300', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                this.lines = data.lines || [];
                this.$nextTick(() => {
                    const box = this.$refs.box;
                    if (box) box.scrollTop = box.scrollHeight;
                });
            } catch (e) { console.error(e); }
        },
        start() {
            this.fetchLogs();
            this.timer = setInterval(() => this.fetchLogs(), 5000);
        },
        toggle() {
            this.paused = !this.paused;
            if (!this.paused) this.fetchLogs();
        },
        async clear() {
            if (!confirm('Clear all logs?')) return;
            await fetch('{{ route('logs.clear') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
            });
            this.lines = [];
        },
        lineClass(line) {
            if (/\.ERROR:/i.test(line) || /Exception/i.test(line)) return 'text-red-400';
            if (/\.WARNING:/i.test(line)) return 'text-amber-400';
            if (/\.INFO:/i.test(line)) return 'text-emerald-400';
            if (/\.DEBUG:/i.test(line)) return 'text-slate-500';
            return 'text-slate-300';
        }
    }
}
</script>
@endsection