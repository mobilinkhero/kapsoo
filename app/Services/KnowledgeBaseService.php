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
                'chunk_index' => $i,
                'metadata' => [
                    'title' => $doc->title,
                    'type' => $doc->type,
                    'source' => $doc->source,
                    'length' => mb_strlen($text),
                ],
            ]);
        }
    }

    /**
     * Recursive character text splitter with sentence/paragraph awareness.
     */
    protected function chunk(string $text, int $size = 700, int $overlap = 120): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($text === '') {
            return [];
        }

        $separators = ["\n\n", "\n", '. ', '? ', '! ', '; ', ', ', ' '];
        $chunks = $this->recursiveSplit($text, $separators, $size, $overlap);

        // Filter tiny chunks by merging into previous
        $out = [];
        foreach ($chunks as $c) {
            $c = trim($c);
            if ($c === '') {
                continue;
            }
            if (! empty($out) && mb_strlen($c) < 80) {
                $out[count($out) - 1] .= ' '.$c;
            } else {
                $out[] = $c;
            }
        }

        return array_values($out);
    }

    protected function recursiveSplit(string $text, array $separators, int $size, int $overlap): array
    {
        if (mb_strlen($text) <= $size) {
            return [$text];
        }

        $sep = null;
        foreach ($separators as $s) {
            if (str_contains($text, $s)) {
                $sep = $s;
                break;
            }
        }

        if ($sep === null) {
            // Hard split
            $chunks = [];
            $len = mb_strlen($text);
            $start = 0;
            while ($start < $len) {
                $chunks[] = mb_substr($text, $start, $size);
                $start += ($size - $overlap);
            }

            return $chunks;
        }

        $parts = explode($sep, $text);
        $chunks = [];
        $current = '';
        foreach ($parts as $part) {
            $candidate = $current === '' ? $part : $current.$sep.$part;
            if (mb_strlen($candidate) <= $size) {
                $current = $candidate;
            } else {
                if ($current !== '') {
                    $chunks[] = $current;
                }
                if (mb_strlen($part) > $size) {
                    $chunks = array_merge($chunks, $this->recursiveSplit($part, array_slice($separators, 1), $size, $overlap));
                    $current = '';
                } else {
                    $current = $part;
                }
            }
        }
        if ($current !== '') {
            $chunks[] = $current;
        }

        // Apply overlap: prepend tail of previous chunk
        if ($overlap > 0 && count($chunks) > 1) {
            $withOverlap = [$chunks[0]];
            for ($i = 1; $i < count($chunks); $i++) {
                $prev = $chunks[$i - 1];
                $tail = mb_substr($prev, max(0, mb_strlen($prev) - $overlap));
                $withOverlap[] = trim($tail.' '.$chunks[$i]);
            }
            $chunks = $withOverlap;
        }

        return $chunks;
    }

    /**
     * Advanced retrieval: hybrid (semantic + keyword) + query expansion + MMR.
     *
     * @return array{chunks: array, scores: array, queries: array}
     */
    public function search(string $query, int $limit = 5, array $options = []): array
    {
        $useExpansion = $options['expand'] ?? true;
        $useHybrid = $options['hybrid'] ?? true;
        $useMmr = $options['mmr'] ?? true;
        $rerank = $options['rerank'] ?? false;

        // Query expansion: generate alternative phrasings
        $queries = [$query];
        if ($useExpansion && $this->openai->isConfigured()) {
            $alt = $this->expandQuery($query);
            foreach ($alt as $q) {
                if ($q && $q !== $query) {
                    $queries[] = $q;
                }
            }
        }

        // HyDE: generate hypothetical document answer and embed
        $hydeEmbedding = null;
        if (($options['hyde'] ?? false) && $this->openai->isConfigured()) {
            $hydeText = $this->hypotheticalDocument($query);
            if ($hydeText) {
                $hydeEmbedding = $this->openai->embed($hydeText);
            }
        }

        $allChunks = KnowledgeChunk::with('document')
            ->whereHas('document', fn ($q) => $q->where('active', true))
            ->get();

        if ($allChunks->isEmpty()) {
            return ['chunks' => [], 'scores' => [], 'queries' => $queries];
        }

        // Embed all queries
        $queryEmbeddings = [];
        foreach ($queries as $q) {
            $e = $this->openai->embed($q);
            if ($e) {
                $queryEmbeddings[] = $e;
            }
        }

        // Score each chunk
        $scored = [];
        foreach ($allChunks as $chunk) {
            $emb = is_array($chunk->embedding) ? $chunk->embedding : null;
            $semantic = 0.0;
            if ($emb) {
                // Max similarity across query embeddings (and HyDE)
                $best = 0.0;
                foreach ($queryEmbeddings as $qe) {
                    $best = max($best, $this->cosine($qe, $emb));
                }
                if ($hydeEmbedding) {
                    $best = max($best, $this->cosine($hydeEmbedding, $emb));
                }
                $semantic = $best;
            }

            $keyword = $useHybrid ? $this->keywordScore($query, $chunk->content) : 0.0;

            // Combine: weighted sum (0.7 semantic, 0.3 keyword) when both available
            if ($useHybrid && $emb) {
                $combined = 0.7 * $semantic + 0.3 * $keyword;
            } elseif ($emb) {
                $combined = $semantic;
            } else {
                $combined = $keyword;
            }

            $scored[] = [
                'chunk' => $chunk,
                'score' => $combined,
                'semantic' => $semantic,
                'keyword' => $keyword,
                'content' => $chunk->content,
                'metadata' => $chunk->metadata ?? [],
            ];
        }

        // Sort by combined score desc
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        // MMR for diversity
        if ($useMmr && count($scored) > $limit) {
            $selected = $this->mmr($scored, $limit, 0.7);
        } else {
            $selected = array_slice($scored, 0, $limit);
        }

        // Optional LLM re-ranking
        if ($rerank && $this->openai->isConfigured() && count($selected) > 1) {
            $selected = $this->rerank($query, $selected, $limit);
        }

        $chunks = array_column($selected, 'content');
        $scores = array_map(fn ($s) => [
            'score' => round($s['score'], 4),
            'semantic' => round($s['semantic'], 4),
            'keyword' => round($s['keyword'], 4),
            'title' => $s['metadata']['title'] ?? null,
        ], $selected);

        return [
            'chunks' => $chunks,
            'scores' => $scores,
            'queries' => $queries,
        ];
    }

    /**
     * Backwards-compatible: return just chunk contents.
     */
    public function searchSimple(string $query, int $limit = 5, array $options = []): array
    {
        $res = $this->search($query, $limit, $options);

        return $res['chunks'];
    }

    protected function expandQuery(string $query): array
    {
        $prompt = 'Generate 3 alternative search queries for the following user question, capturing different phrasings/intents. '
            ."Return ONLY a JSON array of strings, no markdown.\nQuestion: ".$query;
        $res = $this->openai->chat([
            ['role' => 'system', 'content' => 'You are a search query expansion assistant.'],
            ['role' => 'user', 'content' => $prompt],
        ], ['temperature' => 0.5, 'response_format' => ['type' => 'json_object']]);

        if (! $res['ok']) {
            return [];
        }
        $parsed = json_decode($res['content'] ?? '{}', true);
        if (! is_array($parsed)) {
            return [];
        }
        // Accept either {"queries":[...]} or a bare array wrapped
        foreach (['queries', 'alternatives', 'results'] as $key) {
            if (isset($parsed[$key]) && is_array($parsed[$key])) {
                return array_values(array_filter($parsed[$key], 'is_string'));
            }
        }
        // If the json is a list of strings at top-level somehow
        $out = [];
        foreach ($parsed as $v) {
            if (is_string($v)) {
                $out[] = $v;
            } elseif (is_array($v)) {
                foreach ($v as $vv) {
                    if (is_string($vv)) {
                        $out[] = $vv;
                    }
                }
            }
        }

        return $out;
    }

    protected function hypotheticalDocument(string $query): ?string
    {
        $res = $this->openai->chat([
            ['role' => 'system', 'content' => 'You write a short hypothetical passage that would answer the user question.'],
            ['role' => 'user', 'content' => $query],
        ], ['temperature' => 0.5]);
        if (! $res['ok']) {
            return null;
        }

        return $res['content'];
    }

    protected function keywordScore(string $query, string $content): float
    {
        $qTokens = $this->tokenize($query);
        $cTokens = $this->tokenize($content);
        if (empty($qTokens) || empty($cTokens)) {
            return 0.0;
        }

        // Term frequency in content
        $tf = array_count_values($cTokens);
        $total = count($cTokens);
        $score = 0.0;
        foreach (array_unique($qTokens) as $t) {
            if (isset($tf[$t])) {
                $score += ($tf[$t] / $total);
            }
        }
        // Normalize by query length
        $score = $score / max(1, count(array_unique($qTokens)));

        // IDF-ish boost: rare-ish terms; simple length normalization
        return min(1.0, $score * 5);
    }

    protected function tokenize(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $parts = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        // Remove very short tokens
        $out = [];
        foreach ($parts as $p) {
            if (mb_strlen($p) >= 3) {
                $out[] = $p;
            }
        }

        return $out;
    }

    protected function mmr(array $scored, int $k, float $lambda = 0.7): array
    {
        $selected = [];
        $remaining = array_values($scored);
        // Precompute embeddings for similarity among chunks
        $embeddings = [];
        foreach ($remaining as $i => $s) {
            $chunk = $s['chunk'];
            $embeddings[$i] = is_array($chunk->embedding) ? $chunk->embedding : null;
        }

        // Pick first = highest score
        $firstIdx = 0;
        $selected[] = $remaining[$firstIdx];
        unset($remaining[$firstIdx]);
        $remaining = array_values($remaining);

        while (count($selected) < $k && ! empty($remaining)) {
            $bestIdx = null;
            $bestScore = -PHP_FLOAT_MAX;
            foreach ($remaining as $i => $cand) {
                $relevance = $cand['score'];
                // Max similarity to already selected
                $maxSim = 0.0;
                foreach ($selected as $sel) {
                    // find index in original scored for embedding
                }
                // Compute max similarity using embeddings by content match
                $maxSim = 0.0;
                foreach ($selected as $sel) {
                    $selEmb = null;
                    foreach ($scored as $s) {
                        if ($s['content'] === $sel['content'] && is_array($s['chunk']->embedding)) {
                            $selEmb = $s['chunk']->embedding;
                            break;
                        }
                    }
                    $candEmb = is_array($cand['chunk']->embedding) ? $cand['chunk']->embedding : null;
                    if ($selEmb && $candEmb) {
                        $maxSim = max($maxSim, $this->cosine($selEmb, $candEmb));
                    }
                }
                $mmrScore = $lambda * $relevance - (1 - $lambda) * $maxSim;
                if ($mmrScore > $bestScore) {
                    $bestScore = $mmrScore;
                    $bestIdx = $i;
                }
            }
            if ($bestIdx === null) {
                break;
            }
            $selected[] = $remaining[$bestIdx];
            array_splice($remaining, $bestIdx, 1);
        }

        return $selected;
    }

    protected function rerank(string $query, array $selected, int $limit): array
    {
        // Build a prompt asking OpenAI to score each chunk 0-10
        $items = [];
        foreach ($selected as $i => $s) {
            $items[] = ($i + 1).'. '.mb_substr($s['content'], 0, 500);
        }
        $list = implode("\n", $items);
        $prompt = 'Rate the relevance of each passage to the question on a scale 0-10. '
            ."Return ONLY JSON like {\"scores\":[n,...]} in the same order.\nQuestion: ".$query."\nPassages:\n".$list;
        $res = $this->openai->chat([
            ['role' => 'system', 'content' => 'You are a relevance scoring assistant.'],
            ['role' => 'user', 'content' => $prompt],
        ], ['temperature' => 0.0, 'response_format' => ['type' => 'json_object']]);
        if (! $res['ok']) {
            return array_slice($selected, 0, $limit);
        }
        $parsed = json_decode($res['content'] ?? '{}', true);
        $scores = $parsed['scores'] ?? [];
        if (! is_array($scores) || count($scores) !== count($selected)) {
            return array_slice($selected, 0, $limit);
        }
        foreach ($selected as $i => $s) {
            $selected[$i]['score'] = (float) ($scores[$i] ?? $s['score']);
        }
        usort($selected, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($selected, 0, $limit);
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
