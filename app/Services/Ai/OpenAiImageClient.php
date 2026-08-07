<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * Wrapper over OpenAI's Images API (https://api.openai.com/v1/images/*).
 *
 * Two endpoints, picked automatically: /generations for a prompt on its own, and
 * /edits when reference pictures are supplied — that is the only way to make the
 * model reuse the real property photo, the RE/MAX logo and the agent's portrait
 * instead of inventing look-alikes. Both return base64 in data[0].b64_json.
 */
class OpenAiImageClient
{
    /** Social formats offered in the UI, mapped to the sizes the API accepts. */
    public const SIZES = [
        'cuadrado' => '1024x1024',
        'vertical' => '1024x1536',
        'horizontal' => '1536x1024',
    ];

    public const QUALITIES = ['media' => 'medium', 'baja' => 'low'];

    public function __construct(private AiSettings $settings) {}

    /**
     * @param  array<int,array{name:string,bytes:string,mime:string}>  $references
     * @return array{bytes:string,usage:array}
     */
    public function generate(string $prompt, string $format, string $quality, array $references = []): array
    {
        $apiKey = $this->settings->apiKey();
        if (! $apiKey) {
            throw new \RuntimeException('No hay una clave de OpenAI configurada en Ajustes.');
        }

        $size = self::SIZES[$format] ?? self::SIZES['cuadrado'];
        $quality = self::QUALITIES[$quality] ?? 'medium';
        $model = $this->settings->imageModel();

        $request = Http::withToken($apiKey)->timeout(300)->baseUrl('https://api.openai.com/v1');

        $response = $references
            ? $this->edit($request, $references, compact('model', 'prompt', 'size', 'quality'))
            : $request->post('/images/generations', [
                'model' => $model, 'prompt' => $prompt, 'size' => $size,
                'quality' => $quality, 'n' => 1, 'output_format' => 'png',
            ]);

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException("OpenAI ({$model}) respondió con error: {$message}");
        }

        $encoded = $response->json('data.0.b64_json');
        $bytes = $encoded ? base64_decode($encoded, true) : null;
        if (! $bytes) {
            throw new \RuntimeException('OpenAI no devolvió ninguna imagen.');
        }

        return ['bytes' => $bytes, 'usage' => $this->usage($response->json() ?? [])];
    }

    /**
     * /edits is multipart, and every reference goes in the repeated `image[]` field
     * (the GPT image models accept up to 16). No mask: we are compositing, not inpainting.
     */
    private function edit($request, array $references, array $fields)
    {
        foreach ($references as $index => $reference) {
            $request = $request->attach('image[]', $reference['bytes'], $reference['name'], [
                'Content-Type' => $reference['mime'],
            ]);
        }

        return $request->post('/images/edits', [
            'model' => $fields['model'],
            'prompt' => $fields['prompt'],
            'size' => $fields['size'],
            'quality' => $fields['quality'],
            'n' => 1,
        ]);
    }

    private function usage(array $json): array
    {
        $usage = $json['usage'] ?? [];

        return [
            'input_tokens' => (int) ($usage['input_tokens'] ?? 0),
            'output_tokens' => (int) ($usage['output_tokens'] ?? 0),
            'cached_tokens' => (int) ($usage['input_tokens_details']['cached_tokens'] ?? 0),
        ];
    }
}
