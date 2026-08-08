<?php

namespace App\Services\Import;

use App\Models\Property;
use App\Services\PropertyContentManager;
use App\Support\RichTextSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Crea la propiedad a partir de lo que la asesora confirmó en el modal de
 * importación. Las fotos se bajan y se vuelven a subir por el mismo camino que
 * una carga manual, para que pasen por el optimizador y queden en disco propio:
 * las URLs firmadas del portal caducan en una hora.
 */
class PropertyImporter
{
    private const MAX_IMAGES = 30;

    public function __construct(
        private PropertyContentManager $content,
        private RichTextSanitizer $sanitizer,
    ) {
    }

    /** @return array{property: Property, images: int, failed: int} */
    public function create(array $data, array $imageUrls): array
    {
        $code = Property::nextCode();
        $title = Str::limit(trim($data['title']), 160, '');
        $property = Property::create([
            'title' => $title,
            'code' => $code,
            'slug' => Str::slug($title.'-'.$code),
            'district' => $data['district'],
            'type' => $data['type'],
            'operation' => $data['operation'],
            'status' => 'available',
            'price' => $data['price'],
            'currency' => $data['currency'],
            'area' => $data['area'],
            'bedrooms' => $data['bedrooms'] ?? 0,
            'bathrooms' => $data['bathrooms'] ?? 0,
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'description' => $this->sanitizer->clean($data['description'] ?? null),
            'priority' => 0,
            // Se guarda sin publicar a propósito: la ficha importada casi siempre
            // necesita una revisión de textos y precios antes de salir al sitio.
            'is_published' => false,
            'featured' => false,
            'show_in_hero' => false,
        ]);

        foreach ($data['features'] ?? [] as $index => $feature) {
            $property->features()->create([
                'icon' => $feature['icon'] ?? 'info',
                'label' => Str::limit((string) $feature['label'], 80, ''),
                'value' => Str::limit((string) $feature['value'], 160, ''),
                'sort_order' => $index,
            ]);
        }

        [$stored, $failed] = $this->downloadImages($property, $imageUrls);

        return ['property' => $property->fresh(), 'images' => $stored, 'failed' => $failed];
    }

    /** @return array{0: int, 1: int} */
    private function downloadImages(Property $property, array $urls): array
    {
        $stored = 0;
        $failed = 0;
        foreach (array_slice($urls, 0, self::MAX_IMAGES) as $url) {
            $file = $this->download($url);
            if (! $file) {
                $failed++;

                continue;
            }
            $media = $this->content->storeMedia($property, $file);
            if ($stored === 0) {
                $media->update(['is_cover' => true]);
            }
            @unlink($file->getPathname());
            $stored++;
        }

        return [$stored, $failed];
    }

    private function download(string $url): ?UploadedFile
    {
        try {
            $response = Http::timeout(30)->get($url);
            if (! $response->successful()) {
                return null;
            }
            $extension = match ($response->header('Content-Type')) {
                'image/png' => 'png', 'image/webp' => 'webp', 'image/avif' => 'avif',
                default => 'jpg',
            };
            $path = tempnam(sys_get_temp_dir(), 'import').'.'.$extension;
            file_put_contents($path, $response->body());
            if (! @getimagesize($path)) {
                @unlink($path);

                return null;
            }

            return new UploadedFile($path, basename(strtok($url, '?')).'.'.$extension, null, null, true);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
