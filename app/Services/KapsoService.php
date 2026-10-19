<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KapsoService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected ?string $phoneNumberId;

    public function __construct()
    {
        $this->baseUrl = rtrim($this->setting('kapso_base_url', config('services.kapso.base_url', 'https://api.kapso.ai')), '/');
        $this->apiKey = $this->setting('kapso_api_key', config('services.kapso.api_key'));
        $this->phoneNumberId = $this->setting('kapso_phone_number_id', config('services.kapso.phone_number_id'));
    }

    protected function setting(string $key, $default = null)
    {
        try {
            $value = \App\Models\Setting::where('key', $key)->value('value');
            return $value !== null ? $value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->phoneNumberId);
    }

    protected function client()
    {
        return Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->baseUrl($this->baseUrl . '/meta/whatsapp');
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
            Log::error('Kapso sendMessage failed', ['status' => $response->status(), 'body' => $response->body()]);
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