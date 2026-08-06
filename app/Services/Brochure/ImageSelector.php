<?php

namespace App\Services\Brochure;

use App\Models\Property;
use App\Services\Ai\OpenAiClient;

class ImageSelector
{
    private const MAX_CANDIDATES = 24;

    public function __construct(
        private OpenAiClient $ai,
        private VisionImageEncoder $encoder,
    ) {}

    /**
     * @return array{media_ids:int[],cover_media_id:?int,usage:array}
     */
    public function select(Property $property, array $options): array
    {
        $images = $property->media->where('type', 'image')->values();
        $emptyUsage = ['input_tokens' => 0, 'output_tokens' => 0, 'cached_tokens' => 0];

        if ($images->isEmpty()) {
            return ['media_ids' => [], 'cover_media_id' => null, 'usage' => $emptyUsage];
        }

        if (($options['images_mode'] ?? 'manual') === 'manual') {
            return $this->manualSelection($images, $options, $emptyUsage);
        }

        return $this->selectAutomatically($images, $emptyUsage);
    }

    private function manualSelection($images, array $options, array $usage): array
    {
        $validIds = $images->pluck('id')->all();
        $ids = array_values(array_intersect(array_map('intval', $options['selected_image_ids'] ?? []), $validIds));
        $cover = in_array((int) ($options['cover_media_id'] ?? 0), $ids, true)
            ? (int) $options['cover_media_id'] : ($ids[0] ?? null);

        return ['media_ids' => $ids, 'cover_media_id' => $cover, 'usage' => $usage];
    }

    private function selectAutomatically($images, array $emptyUsage): array
    {
        $candidates = $images->take(self::MAX_CANDIDATES);
        $maxCount = min((int) config('brochure_templates.max_images.max'), $candidates->count());
        $urls = [];
        $ids = [];
        foreach ($candidates as $media) {
            $urls[] = $this->encoder->fromDisk($media->disk, $media->path);
            $ids[] = $media->id;
        }

        $prompt = 'Estas son fotos de una propiedad inmobiliaria en venta o alquiler, en este orden de ids: '
            .implode(', ', $ids).". Decide cuántas fotos usar (de 1 a {$maxCount}) para el brochure. "
            .'Elige solo las necesarias para mostrar ambientes y ángulos distintos; prioriza buena luz y encuadre '
            .'y evita fotos borrosas, oscuras o repetidas. Elige exactamente una portada, incluida entre las fotos '
            .'seleccionadas.';
        $schema = [
            'name' => 'image_selection',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'selected_media_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                    'cover_media_id' => ['type' => 'integer'],
                ],
                'required' => ['selected_media_ids', 'cover_media_id'],
                'additionalProperties' => false,
            ],
        ];
        $result = $this->ai->vision($prompt, $urls, $schema);
        $selected = array_values(array_unique(array_intersect(
            array_map('intval', $result['data']['selected_media_ids'] ?? []), $ids
        )));
        if (! $selected) {
            $selected = array_slice($ids, 0, min((int) config('brochure_templates.max_images.default'), $maxCount));
        }
        $selected = array_slice($selected, 0, $maxCount);
        $cover = in_array((int) ($result['data']['cover_media_id'] ?? 0), $selected, true)
            ? (int) $result['data']['cover_media_id'] : $selected[0];

        return ['media_ids' => $selected, 'cover_media_id' => $cover, 'usage' => $result['usage'] ?? $emptyUsage];
    }
}
