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
