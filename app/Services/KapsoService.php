<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KapsoService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected ?string $phoneNumberId;

    public function __construct()
    {
        $raw = $this->setting('kapso_base_url', config('services.kapso.base_url', 'https://api.kapso.ai/meta/whatsapp/v24.0'));
        $raw = rtrim($raw, '/');

        // Normalize legacy/short values to the full Kapso Meta Proxy base URL
        if ($raw === 'https://api.kapso.ai' || $raw === 'http://api.kapso.ai') {
            $raw = 'https://api.kapso.ai/meta/whatsapp/v24.0';
        } elseif (str_ends_with($raw, '/meta/whatsapp')) {
            $raw .= '/v24.0';
        } elseif (!preg_match('#/v\d+\.\d+$#', $raw) && str_contains($raw, 'api.kapso.ai')) {
            // append version if missing
            $raw .= '/v24.0';
        }

        $this->baseUrl = $raw;
        $this->apiKey = $this->setting('kapso_api_key', config('services.kapso.api_key'));
        $this->phoneNumberId = $this->setting('kapso_phone_number_id', config('services.kapso.phone_number_id'));
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->phoneNumberId);
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
            'X-API-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Expect' => '',
        ])->baseUrl($this->baseUrl);
    }

    public function sendMessage(string $to, string $text): array
    {
        $response = $this->client()->post("/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text],
        ]);

        if ($response->failed()) {
            Log::error('Kapso sendMessage failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $this->baseUrl,
            ]);
        } else {
            Log::info('Kapso sendMessage success', ['response' => $response->json()]);
        }

        return $response->json() ?? [];
    }

    public function sendTemplate(string $to, string $templateName, string $language = 'en_US', array $components = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ];

        $response = $this->client()->post("/{$this->phoneNumberId}/messages", $payload);

        if ($response->failed()) {
            Log::error('Kapso sendTemplate failed', ['status' => $response->status(), 'body' => $response->body()]);
        }

        return $response->json() ?? [];
    }

    public function listTemplates(): array
    {
        $response = $this->client()->get("/{$this->phoneNumberId}/message_templates");

        if ($response->failed()) {
            Log::error('Kapso listTemplates failed', ['status' => $response->status(), 'body' => $response->body()]);
            return [];
        }

        return $response->json('data') ?? [];
    }

    public function testConnection(): array
    {
        if (empty($this->apiKey)) {
            return ['ok' => false, 'status' => 0, 'body' => ['error' => 'API key missing']];
        }

        // Verify the API key against the platform API (lists connected numbers)
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ])->get('https://api.kapso.ai/platform/v1/whatsapp/phone_numbers');

        $body = $response->json();
        if (!is_array($body)) {
            $body = ['raw' => $response->body()];
        }

        // Also check if the configured phone number ID is in the list
        $found = false;
        if (isset($body['data']) && is_array($body['data'])) {
            foreach ($body['data'] as $num) {
                if (($num['phone_number_id'] ?? '') === $this->phoneNumberId) {
                    $found = true;
                    break;
                }
            }
        }

        return [
            'ok' => $response->successful() && $found,
            'status' => $response->status(),
            'body' => $body,
            'phone_number_found' => $found,
        ];
    }

    public function markAsRead(string $messageId): array
    {
        $response = $this->client()->post("/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ]);

        return $response->json() ?? [];
    }
}