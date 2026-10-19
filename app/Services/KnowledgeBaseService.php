<?php

namespace App\Services;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;

class KnowledgeBaseService
{
    public function __construct(protected OpenAIService $openai) {}

    public function addDocument(string $title, string $content, string $type = 'text', ?string $source = null): KnowledgeDocument
    {
        $doc = KnowledgeDocument::create([
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'source' => $source,
            'active' => true,
        ]);

        $this->indexDocument($doc);

        return $doc;
    }

    public function updateDocument(KnowledgeDocument $doc, array $data): KnowledgeDocument
    {
        $doc->update($data);
        $doc->chunks()->delete();
        $this->indexDocument($doc->fresh());

        return $doc->fresh();
    }

    protected function indexDocument(KnowledgeDocument $doc): void
    {
        $chunks = $this->chunk($doc->content);
        $embeddings = $this->openai->embedMany($chunks);

        foreach ($chunks as $i => $text) {
            KnowledgeChunk::create([
                'knowledge_document_id' => $doc->id,
                'content' => $text,
                'embedding' => $embeddings[$i] ?? null,
            ]);
        }
    }

    protected function chunk(string $text, int $size = 800, int $overlap = 100): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($text === '') {
            return [];
        }
        $chunks = [];
        $len = mb_strlen($text);
        $start = 0;
        while ($start < $len) {
            $piece = mb_substr($text, $start, $size);
            $chunks[] = $piece;
            if ($start + $size >= $len) {
                break;
            }
            $start += ($size - $overlap);
        }

        return $chunks;
    }

    public function search(string $query, int $limit = 5): array
    {
        $qEmbedding = $this->openai->embed($query);
        if (! $qEmbedding) {
            // Fallback: simple keyword match
            return KnowledgeChunk::whereHas('document', fn ($q) => $q->where('active', true))
                ->where('content', 'like', '%'.substr($query, 0, 60).'%')
                ->limit($limit)
                ->pluck('content')
                ->all();
        }

        $chunks = KnowledgeChunk::with('document')->whereHas('document', fn ($q) => $q->where('active', true))->get();
        $scored = [];
        foreach ($chunks as $chunk) {
            if (! is_array($chunk->embedding)) {
                continue;
            }
            $score = $this->cosine($qEmbedding, $chunk->embedding);
            $scored[] = ['score' => $score, 'content' => $chunk->content];
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_column(array_slice($scored, 0, $limit), 'content');
    }

    protected function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        $n = min(count($a), count($b));
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }
        if ($na == 0 || $nb == 0) {
            return 0.0;
        }

        return $dot / (sqrt($na) * sqrt($nb));
    }
}
