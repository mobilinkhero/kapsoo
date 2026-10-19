@extends('layouts.app')
@section('title', 'Add Knowledge')
@section('content')
<div class="max-w-2xl">
    <h2 class="text-2xl font-bold text-slate-900 mb-4">Add Knowledge Document</h2>
    <form method="POST" action="{{ route('knowledge.store') }}" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Title</label>
            <input name="title" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Type</label>
            <select name="type" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
                <option value="text">Text</option>
                <option value="faq">FAQ</option>
                <option value="playbook">Playbook</option>
                <option value="product">Product</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Source (optional)</label>
            <input name="source" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
        </div>
        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-2">
            <label class="block text-sm font-medium text-slate-700">Upload files (PDF, TXT, MD, CSV, JSON)</label>
            <input type="file" id="fileInput" accept=".pdf,.txt,.md,.csv,.json,.log" multiple class="w-full text-sm text-slate-600">
            <p class="text-xs text-slate-500">Text is extracted in your browser and placed into the Content field below. PDFs use pdf.js.</p>
            <div id="fileStatus" class="text-xs text-slate-500"></div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Content</label>
            <textarea name="content" id="contentArea" rows="12" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none"></textarea>
        </div>
        <div class="flex gap-3">
            <button class="px-6 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-600 transition">Save & Index</button>
            <a href="{{ route('knowledge.index') }}" class="px-6 py-3 rounded-xl bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">Cancel</a>
        </div>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function () {
    if (window.pdfjsLib) { pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js'; }
    const input = document.getElementById('fileInput');
    const area = document.getElementById('contentArea');
    const status = document.getElementById('fileStatus');
    if (!input) return;

    async function extractPdf(file) {
        const buf = await file.arrayBuffer();
        const pdf = await pdfjsLib.getDocument({ data: buf }).promise;
        let text = '';
        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const content = await page.getTextContent();
            text += content.items.map(it => it.str).join(' ') + '\n';
        }
        return text.trim();
    }

    async function extractText(file) {
        if (file.name.toLowerCase().endsWith('.pdf')) {
            return await extractPdf(file);
        }
        return await file.text();
    }

    input.addEventListener('change', async () => {
        const files = Array.from(input.files || []);
        if (!files.length) return;
        status.textContent = 'Extracting...';
        let combined = area.value ? area.value + '\n\n' : '';
        for (const f of files) {
            try {
                const t = await extractText(f);
                combined += '===== ' + f.name + ' =====\n' + t + '\n\n';
                status.textContent = 'Extracted: ' + f.name;
            } catch (e) {
                status.textContent = 'Failed to extract ' + f.name + ': ' + e.message;
            }
        }
        area.value = combined.trim();
        const titleInput = document.querySelector('input[name=title]');
        if (titleInput && !titleInput.value && files.length) {
            titleInput.value = files[0].name.replace(/\.[^.]+$/, '');
        }
        status.textContent = 'Done. ' + files.length + ' file(s) extracted.';
    });
})();
</script>
@endsection