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

        $count = max(1, min((int) ($options['image_count'] ?? 6), $images->count()));

        if (($options['images_mode'] ?? 'manual') === 'manual') {
            $validIds = $images->pluck('id')->all();
            $ids = array_values(array_intersect(
                array_map('intval', $options['selected_image_ids'] ?? []),
                $validIds
            ));
            if (! $ids) {
                $ids = $images->take($count)->pluck('id')->all();
            }
            $cover = in_array((int) ($options['cover_media_id'] ?? 0), $ids, true)
                ? (int) $options['cover_media_id']
                : $ids[0];

            return ['media_ids' => $ids, 'cover_media_id' => $cover, 'usage' => $emptyUsage];
        }

        return $this->selectAutomatically($images, $count, $emptyUsage);
    }

    private function selectAutomatically($images, int $count, array $emptyUsage): array
    {
        $candidates = $images->take(self::MAX_CANDIDATES);
        $urls = [];
        $ids = [];
        foreach ($candidates as $media) {
            $urls[] = $this->encoder->fromDisk($media->disk, $media->path);
            $ids[] = $media->id;
        }

        $prompt = 'Estas son fotos de una propiedad inmobiliaria en venta/alquiler, en este orden de ids: '
            .implode(', ', $ids).". Elige exactamente {$count} para un brochure de ventas (prioriza buena luz, "
            .'encuadre y que muestren ambientes/ángulos distintos; evita fotos borrosas, oscuras o repetidas) '
            .'y decide cuál es la mejor portada (imagen de mayor impacto).';

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
        $data = $result['data'] ?? [];

        $selected = array_values(array_intersect(
            array_map('intval', $data['selected_media_ids'] ?? []),
            $ids
        ));
        if (! $selected) {
            $selected = array_slice($ids, 0, $count);
        }
        $selected = array_slice($selected, 0, $count);

        $cover = in_array((int) ($data['cover_media_id'] ?? 0), $selected, true)
            ? (int) $data['cover_media_id']
            : $selected[0];

        return ['media_ids' => $selected, 'cover_media_id' => $cover, 'usage' => $result['usage'] ?? $emptyUsage];
    }
}
