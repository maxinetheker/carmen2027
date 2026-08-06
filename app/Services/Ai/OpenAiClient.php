<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over OpenAI's Responses API (https://api.openai.com/v1/responses).
 * Deliberately not a third-party SDK: gpt-5.6's vision + web_search + structured
 * output combination is too new to trust a wrapper package to already cover it.
 */
class OpenAiClient
{
    public function __construct(private AiSettings $settings) {}

    public function isConfigured(): bool
    {
        return (bool) $this->settings->apiKey();
    }

    public function text(string $prompt, ?array $schema = null, bool $webSearch = false, ?string $instructions = null): array
    {
        return $this->respond([['type' => 'input_text', 'text' => $prompt]], $schema, $webSearch, $instructions);
    }

    /**
     * @param  string[]  $imageUrls  http(s):// or data: URIs
     */
    public function vision(string $prompt, array $imageUrls, ?array $schema = null, ?string $instructions = null): array
    {
        $content = [['type' => 'input_text', 'text' => $prompt]];
        foreach ($imageUrls as $url) {
            $content[] = ['type' => 'input_image', 'image_url' => $url];
        }

        return $this->respond($content, $schema, false, $instructions);
    }

    /**
     * @param  array<int,array<string,mixed>>  $content
     * @param  array{name:string,schema:array}|null  $schema
     * @return array{text:string,data:?array,sources:array,usage:array}
     */
    private function respond(array $content, ?array $schema, bool $webSearch, ?string $instructions): array
    {
        $apiKey = $this->settings->apiKey();
        if (! $apiKey) {
            throw new \RuntimeException('No hay una clave de OpenAI configurada en Ajustes.');
        }

        $payload = [
            'model' => $this->settings->model(),
            'input' => [['role' => 'user', 'content' => $content]],
        ];

        if ($instructions) {
            $payload['instructions'] = $instructions;
        }

        if ($webSearch) {
            $payload['tools'] = [['type' => 'web_search']];
            $payload['include'] = ['web_search_call.action.sources'];
        }

        if ($schema) {
            $payload['text'] = ['format' => [
                'type' => 'json_schema',
                'name' => $schema['name'],
                'schema' => $schema['schema'],
                'strict' => true,
            ]];
        }

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->baseUrl('https://api.openai.com/v1')
            ->post('/responses', $payload);

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException("OpenAI ({$this->settings->model()}) respondió con error: {$message}");
        }

        $json = $response->json() ?? [];
        $text = $this->extractOutputText($json);

        return [
            'text' => $text,
            'data' => $schema ? json_decode($text, true) : null,
            'sources' => $this->extractSources($json),
            'usage' => $this->extractUsage($json),
        ];
    }

    private function extractOutputText(array $json): string
    {
        if (is_string($json['output_text'] ?? null)) {
            return $json['output_text'];
        }

        $text = '';
        foreach ($json['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }
            foreach ($item['content'] ?? [] as $part) {
                if (($part['type'] ?? null) === 'output_text') {
                    $text .= (string) ($part['text'] ?? '');
                }
            }
        }

        return $text;
    }

    private function extractSources(array $json): array
    {
        $sources = [];
        foreach ($json['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'web_search_call') {
                continue;
            }
            foreach ($item['action']['sources'] ?? [] as $source) {
                $url = is_array($source) ? ($source['url'] ?? null) : $source;
                if ($url) {
                    $sources[] = $url;
                }
            }
        }

        return array_values(array_unique($sources));
    }

    private function extractUsage(array $json): array
    {
        $usage = $json['usage'] ?? [];

        return [
            'input_tokens' => (int) ($usage['input_tokens'] ?? 0),
            'output_tokens' => (int) ($usage['output_tokens'] ?? 0),
            'cached_tokens' => (int) ($usage['input_tokens_details']['cached_tokens'] ?? 0),
        ];
    }
}
