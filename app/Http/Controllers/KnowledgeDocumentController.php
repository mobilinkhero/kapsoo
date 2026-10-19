<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeDocument;
use App\Services\KnowledgeBaseService;
use Illuminate\Http\Request;

class KnowledgeDocumentController extends Controller
{
    public function __construct(protected KnowledgeBaseService $kb) {}

    public function index()
    {
        $documents = KnowledgeDocument::orderByDesc('created_at')->paginate(20);

        return view('knowledge.index', compact('documents'));
    }

    public function create()
    {
        return view('knowledge.create');
    }

    public function show(KnowledgeDocument $knowledge)
    {
        return redirect()->route('knowledge.edit', $knowledge);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:255',
        ]);

        $this->kb->addDocument($data['title'], $data['content'], $data['type'] ?? 'text', $data['source'] ?? null);

        return redirect()->route('knowledge.index')->with('success', 'Knowledge document added and indexed.');
    }

    public function edit(KnowledgeDocument $knowledge)
    {
        $knowledgeDocument = $knowledge;

        return view('knowledge.edit', compact('knowledgeDocument'));
    }

    public function update(Request $request, KnowledgeDocument $knowledge)
    {
        $knowledgeDocument = $knowledge;
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
        ]);
        $data['active'] = $request->boolean('active', true);

        $this->kb->updateDocument($knowledgeDocument, $data);

        return redirect()->route('knowledge.index')->with('success', 'Knowledge document updated.');
    }

    public function destroy(KnowledgeDocument $knowledge)
    {
        $knowledge->delete();

        return redirect()->route('knowledge.index')->with('success', 'Knowledge document deleted.');
    }
}
