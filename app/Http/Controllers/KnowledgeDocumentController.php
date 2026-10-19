<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeDocument;
use App\Services\KnowledgeBaseService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class KnowledgeDocumentController extends Controller
{
    public function __construct(protected KnowledgeBaseService $kb, protected OpenAIService $openai) {}

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

    public function importUrl(Request $request)
    {
        $data = $request->validate([
            'url' => 'required|url',
            'title' => 'nullable|string|max:255',
        ]);

        $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($data['url']);
        if ($response->failed()) {
            return redirect()->route('knowledge.index')->with('error', 'Failed to fetch URL.');
        }

        $html = $response->body();
        // Extract title if not provided
        $title = $data['title'] ?? null;
        if (! $title && preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $title = trim(html_entity_decode(strip_tags($m[1])));
        }
        $title = $title ?: parse_url($data['url'], PHP_URL_HOST);

        // Strip scripts/styles and tags, normalize whitespace
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) < 50) {
            return redirect()->route('knowledge.index')->with('error', 'Not enough text extracted from URL.');
        }

        $this->kb->addDocument($title, $text, 'web', $data['url']);

        return redirect()->route('knowledge.index')->with('success', 'Imported and indexed: '.$title);
    }

    public function test(Request $request)
    {
        $query = $request->get('q');
        $chunks = [];
        $answer = null;
        if ($query) {
            $searchResult = $this->kb->search($query, 6, ['rerank' => false]);
            $chunks = $searchResult['chunks'] ?? [];
            $scores = $searchResult['scores'] ?? [];
            $queries = $searchResult['queries'] ?? [$query];
            if ($this->openai->isConfigured()) {
                $context = empty($chunks) ? '' : "Knowledge base context:\n- ".implode("\n- ", $chunks);
                $messages = [
                    ['role' => 'system', 'content' => 'You are a helpful assistant. Answer using the provided knowledge base context when relevant. Be concise.'],
                ];
                if ($context) {
                    $messages[] = ['role' => 'system', 'content' => $context];
                }
                $messages[] = ['role' => 'user', 'content' => $query];
                $res = $this->openai->chat($messages, ['temperature' => 0.3]);
                $answer = $res['ok'] ? $res['content'] : ('Error: '.($res['error'] ?? 'unknown'));
            }
        }

        return view('knowledge.test', compact('query', 'chunks', 'scores', 'queries', 'answer'));
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
