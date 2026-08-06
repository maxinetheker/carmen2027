<?php

namespace App\Services\Brochure;

use App\Services\Ai\AiSettings;
use App\Services\Ai\OpenAiClient;

/**
 * "Automático" logo mode: the AI picks which of the brand's logo variants fits the
 * chosen template best. A quick light/dark pre-filter narrows the 7 files down to 3
 * relevant candidates first, so each generation only sends 3 small (512px) images to
 * vision instead of all 7 — the model still makes the real call, just among options
 * that could plausibly work, keeping token usage down.
 */
class LogoSelector
{
    public function __construct(
        private OpenAiClient $ai,
        private VisionImageEncoder $encoder,
        private AiSettings $aiSettings,
    ) {}

    /**
     * @return array{key:?string,usage:array}
     */
    public function select(array $options, array $theme): array
    {
        $emptyUsage = ['input_tokens' => 0, 'output_tokens' => 0, 'cached_tokens' => 0];
        $mode = $options['logo_mode'] ?? 'auto';
        $logos = config('brochure_templates.logos');
        $default = config('brochure_templates.default_logo');

        if ($mode === 'off') {
            return ['key' => null, 'usage' => $emptyUsage];
        }

        if ($mode === 'manual') {
            $key = isset($logos[$options['logo_key'] ?? null]) ? $options['logo_key'] : $default;

            return ['key' => $key, 'usage' => $emptyUsage];
        }

        $candidateKeys = $this->candidates($theme);
        $images = [];
        foreach ($candidateKeys as $key) {
            if ($path = $this->logoPath($logos, $key)) {
                $images[$key] = $this->encoder->encode(file_get_contents($path), 512);
            }
        }
        if (! $images) {
            return ['key' => $default, 'usage' => $emptyUsage];
        }

        $keys = array_keys($images);
        $prompt = 'Estas son variantes del mismo logo de la inmobiliaria, en este orden de claves: '
            .implode(', ', $keys).'. El brochure usa fondo de pie de página '.$theme['primary']
            .' y color de acento '.$theme['accent'].'. Elige la variante ("logo_key") que se vea con mejor '
            .'contraste y más limpia sobre ese fondo. Ante resultados similares, prefiere la variante símbolo '
            .'(sin letras) por ser más discreta.';

        $schema = [
            'name' => 'logo_selection',
            'schema' => [
                'type' => 'object',
                'properties' => ['logo_key' => ['type' => 'string', 'enum' => $keys]],
                'required' => ['logo_key'],
                'additionalProperties' => false,
            ],
        ];

        $instructions = PromptContext::instructions($this->aiSettings->basePrompt(), null);
        $result = $this->ai->vision($prompt, array_values($images), $schema, $instructions);
        $key = $result['data']['logo_key'] ?? null;

        return [
            'key' => in_array($key, $keys, true) ? $key : $default,
            'usage' => $result['usage'] ?? $emptyUsage,
        ];
    }

    /**
     * @return string[]
     */
    private function candidates(array $theme): array
    {
        $luminance = $this->luminance($theme['primary']);
        $variant = $luminance < 0.5
            ? ['horizontal_white', 'vertical_color_silver_text']
            : ['horizontal_color', 'vertical_silver'];

        return array_merge(['symbol'], $variant);
    }

    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    }

    private function logoPath(array $logos, string $key): ?string
    {
        $path = storage_path(config('brochure_templates.logos_path').'/'.($logos[$key]['file'] ?? ''));

        return is_file($path) ? $path : null;
    }
}
