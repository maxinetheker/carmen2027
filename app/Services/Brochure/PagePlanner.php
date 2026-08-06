<?php

namespace App\Services\Brochure;

use App\Models\Property;
use App\Services\Ai\AiSettings;
use App\Services\Ai\OpenAiClient;
use Illuminate\Support\Facades\Log;

/**
 * Chooses how many of the possible, already-designed pages deserve to be shown.
 * The user supplies a hard ceiling; the model can only choose within that ceiling
 * and is explicitly told not to create a sparse page.
 */
class PagePlanner
{
    public function __construct(private OpenAiClient $ai, private AiSettings $settings) {}

    /**
     * @return array{page_count:int,usage:array}
     */
    public function decide(Property $property, array $options, array $generated): array
    {
        $emptyUsage = ['input_tokens' => 0, 'output_tokens' => 0, 'cached_tokens' => 0];
        $limit = min(
            (int) config('brochure_templates.max_pages.max'),
            max(1, (int) ($options['max_pages'] ?? config('brochure_templates.max_pages.default')))
        );
        $available = $this->availablePages($property, $generated);
        $fallback = min($limit, $available);

        if ($limit === 1 || $available === 1 || ! $this->ai->isConfigured()) {
            return ['page_count' => $fallback, 'usage' => $emptyUsage];
        }

        $prompt = PromptContext::propertySummary($property)
            ."\n\nDebes decidir cuántas hojas A4 necesita este brochure. La portada ya ocupa 1 hoja. "
            ."El límite absoluto elegido por la asesora es {$limit} hojas y el material disponible permite como "
            ."máximo {$available}. Fotos adicionales después de la portada: ".count($generated['media_ids'] ?? []).". "
            .'Hay contenido técnico: '.($this->hasTechnicalContent($property, $generated) ? 'sí' : 'no').'. '
            .'Usa la menor cantidad de hojas que mantenga cada hoja útil y visualmente llena. No reserves una '
            .'hoja para contenido escaso, no excedas el límite y nunca cuentes una hoja en blanco. Devuelve solo '
            .'la cantidad total de hojas, incluida la portada.';

        $schema = [
            'name' => 'brochure_page_plan',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'page_count' => ['type' => 'integer', 'minimum' => 1, 'maximum' => $fallback],
                ],
                'required' => ['page_count'],
                'additionalProperties' => false,
            ],
        ];

        try {
            $instructions = PromptContext::instructions($this->settings->basePrompt(), $options['extra_prompt'] ?? null);
            $result = $this->ai->text($prompt, $schema, false, $instructions);
            $pageCount = (int) ($result['data']['page_count'] ?? $fallback);

            return [
                'page_count' => max(1, min($fallback, $pageCount)),
                'usage' => $result['usage'] ?? $emptyUsage,
            ];
        } catch (\Throwable $e) {
            Log::warning('No se pudo planificar la cantidad de hojas del brochure; se aplicó el límite seguro.', [
                'error' => $e->getMessage(),
            ]);

            return ['page_count' => $fallback, 'usage' => $emptyUsage];
        }
    }

    private function availablePages(Property $property, array $generated): int
    {
        $photoCount = max(0, count($generated['media_ids'] ?? []) - 1);
        $pages = 1;

        if ($photoCount > 0 || ! empty($generated['interest'])) {
            $pages++;
        }
        if ($this->hasTechnicalContent($property, $generated)) {
            $pages++;
        }

        // A gallery-only sheet is permitted only when it has at least three photos.
        $remainingPhotos = max(0, $photoCount - 4);
        $pages += intdiv($remainingPhotos, 6);

        return max(1, $pages);
    }

    private function hasTechnicalContent(Property $property, array $generated): bool
    {
        return ! empty($generated['croquis_svg'])
            || ! empty($generated['faqs'])
            || trim(strip_tags((string) $property->description)) !== '';
    }
}
