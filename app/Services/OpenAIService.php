<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected ?string $apiKey;

    protected string $model;

    protected string $embeddingModel;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = $this->setting('openai_api_key', config('services.openai.api_key'));
        $this->model = $this->setting('openai_model', config('services.openai.model', 'gpt-4o-mini'));
        $this->embeddingModel = $this->setting('openai_embedding_model', config('services.openai.embedding_model', 'text-embedding-3-small'));
        $this->baseUrl = rtrim($this->setting('openai_base_url', config('services.openai.base_url', 'https://api.openai.com/v1')), '/');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    public function testConnection(): array
    {
        if (empty($this->apiKey)) {
            return ['ok' => false, 'status' => 0, 'body' => ['error' => 'API key missing']];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Accept' => 'application/json',
        ])->get($this->baseUrl.'/models');

        $body = $response->json();
        if (! is_array($body)) {
            $body = ['raw' => $response->body()];
        }

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $body,
        ];
    }

    protected function setting(string $key, $default = null)
    {
        try {
            $value = Setting::where('key', $key)->value('value');

            return $value !== null ? $value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    protected function client()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->baseUrl($this->baseUrl)->timeout(60);
    }

    public function chat(array $messages, array $options = []): array
    {
        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => (float) ($options['temperature'] ?? 0.4),
        ], $options);
        unset($payload['temperature']);
        $payload['temperature'] = (float) ($options['temperature'] ?? 0.4);

        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        $response = $this->client()->post('/chat/completions', $payload);

        if ($response->failed()) {
            Log::error('OpenAI chat failed', ['status' => $response->status(), 'body' => $response->body()]);

            return ['ok' => false, 'error' => $response->json('error.message') ?? $response->body(), 'content' => null];
        }

        $content = $response->json('choices.0.message.content');

        return ['ok' => true, 'content' => $content, 'raw' => $response->json()];
    }

    public function embed(string $text): ?array
    {
        $response = $this->client()->post('/embeddings', [
            'model' => $this->embeddingModel,
            'input' => $text,
        ]);

        if ($response->failed()) {
            Log::error('OpenAI embed failed', ['status' => $response->status(), 'body' => $response->body()]);

            return null;
        }

        return $response->json('data.0.embedding');
    }

    public function transcribe(string $mediaUrl): ?string
    {
        if (empty($this->apiKey) || empty($mediaUrl)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0',
            ])->timeout(60)->get($mediaUrl);

            if ($response->failed()) {
                Log::error('Whisper download failed', ['status' => $response->status(), 'url' => $mediaUrl]);

                return null;
            }

            $contents = $response->body();
            $path = parse_url($mediaUrl, PHP_URL_PATH) ?: 'audio.ogg';
            $filename = basename($path);
            if (! pathinfo($filename, PATHINFO_EXTENSION)) {
                $filename .= '.ogg';
            }

            $res = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])->timeout(120)->attach('file', $contents, $filename)->post($this->baseUrl.'/audio/transcriptions', [
                'model' => 'whisper-1',
                'response_format' => 'text',
            ]);

            if ($res->failed()) {
                Log::error('Whisper transcription failed', ['status' => $res->status(), 'body' => $res->body()]);

                return null;
            }

            return trim($res->body());
        } catch (\Throwable $e) {
            Log::error('Whisper exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function extractPaymentDetails(string $imageUrl): ?array
    {
        if (empty($this->apiKey) || empty($imageUrl)) {
            return null;
        }

        $prompt = 'Analyze this image. If it is a payment receipt/screenshot (JazzCash, bank transfer, etc.), extract these fields as JSON: '
            .'{ "is_payment": true/false, "amount": number or null, "currency": string or null, "recipient_number": string or null, '
            .'"recipient_name": string or null, "transaction_id": string or null, "date": string or null, "notes": string or null }. '
            .'If it is NOT a payment screenshot, return {"is_payment": false}. Respond ONLY with valid JSON, no markdown.';

        $messages = [[
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
            ],
        ]];

        $res = $this->chat($messages, ['temperature' => 0.1, 'response_format' => ['type' => 'json_object']]);

        if (! $res['ok']) {
            return null;
        }

        $parsed = json_decode($res['content'] ?? '{}', true);

        return is_array($parsed) ? $parsed : null;
    }

    public function visionDescribe(string $imageUrl, string $prompt = 'Describe what this image shows in one or two sentences, focusing on any product, issue, or request relevant to customer support.'): ?string
    {
        if (empty($this->apiKey) || empty($imageUrl)) {
            return null;
        }

        $messages = [[
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
            ],
        ]];

        $res = $this->chat($messages, ['temperature' => 0.2]);

        if (! $res['ok']) {
            return null;
        }

        return trim((string) $res['content']);
    }

    public function summarizeMemory(array $log): ?string
    {
        if (empty($this->apiKey) || empty($log)) {
            return null;
        }

        $lines = [];
        foreach ($log as $entry) {
            $lines[] = 'Customer: '.($entry['inbound'] ?? '').' | AI: '.($entry['reply'] ?? '').' | tags: '.implode(',', (array) ($entry['tags'] ?? []));
        }

        $prompt = "Summarize this customer's history into a concise 3-4 sentence profile capturing their needs, preferences, issues, and current lead status. Be factual and avoid repetition.\n\n".implode("\n", $lines);

        $res = $this->chat([
            ['role' => 'system', 'content' => 'You are a CRM assistant that writes concise customer profiles.'],
            ['role' => 'user', 'content' => $prompt],
        ], ['temperature' => 0.3]);

        if (! $res['ok']) {
            return null;
        }

        return trim((string) $res['content']);
    }

    public function embedMany(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }
        $response = $this->client()->post('/embeddings', [
            'model' => $this->embeddingModel,
            'input' => array_values($texts),
        ]);

        if ($response->failed()) {
            Log::error('OpenAI embedMany failed', ['status' => $response->status(), 'body' => $response->body()]);

            return [];
        }

        $out = [];
        foreach ($response->json('data', []) as $row) {
            $out[$row['index']] = $row['embedding'];
        }
        ksort($out);

        return array_values($out);
    }
}
