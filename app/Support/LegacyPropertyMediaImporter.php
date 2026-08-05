<?php

namespace App\Support;

use App\Models\Property;
use App\Services\ImageBorderCropper;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class LegacyPropertyMediaImporter
{
    public function __construct(private readonly ImageBorderCropper $cropper) {}

    public function import(Property $property, array $row): void
    {
        $files = [];
        foreach (['fotos_general', 'fotos_areas_comunes', 'fotos_alrededores'] as $field) {
            $decoded = json_decode(trim((string) ($row[$field] ?? '[]')), true, flags: JSON_THROW_ON_ERROR);
            foreach ((array) $decoded as $file) $files[] = ['image', (string) $file];
        }
        if ($video = trim((string) ($row['video'] ?? ''))) $files[] = ['video', $video];

        $seen = [];
        foreach ($files as $order => [$type, $filename]) {
            $filename = basename(trim($filename));
            if ($filename === '' || isset($seen[$filename])) continue;
            $seen[$filename] = true;
            $source = database_path('legacy/media/'.$filename);
            if (! is_file($source)) throw new RuntimeException("Falta el archivo histórico {$filename}.");
            $path = "properties/{$property->id}/legacy/{$filename}";
            Storage::disk('public')->makeDirectory(dirname($path));
            if (! copy($source, Storage::disk('public')->path($path))) {
                throw new RuntimeException("No se pudo copiar {$filename}.");
            }
            if ($type === 'image') {
                $this->cropper->trimWebpFile(Storage::disk('public')->path($path));
            }
            $image = $type === 'image' ? @getimagesize($source) : null;
            if ($type === 'image') $image = @getimagesize(Storage::disk('public')->path($path));
            $property->media()->create([
                'type' => $type, 'disk' => 'public', 'path' => $path, 'original_name' => $filename,
                'mime_type' => $image['mime'] ?? ($type === 'video' ? 'video/mp4' : 'image/webp'),
                'sort_order' => $order, 'is_cover' => $type === 'image' && ! $property->media()->where('type', 'image')->exists(),
                'width' => $image[0] ?? null, 'height' => $image[1] ?? null,
                'size_bytes' => filesize(Storage::disk('public')->path($path)) ?: null,
            ]);
        }
    }
}
