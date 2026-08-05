<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PropertySharePreview
{
    private const WIDTH = 1200;
    private const HEIGHT = 630;

    public function create(Property $property): string
    {
        $property->loadMissing('media');
        $sourcePath = $this->sourcePath($property);
        $previewPath = "properties/{$property->id}/share-preview.jpg";
        $disk = Storage::disk('public');
        $absolutePreview = $disk->path($previewPath);
        $sourceModified = max(
            filemtime($sourcePath) ?: 0,
            $property->updated_at?->getTimestamp() ?? 0
        );
        if (is_file($absolutePreview) && filemtime($absolutePreview) >= $sourceModified) {
            return $absolutePreview;
        }

        $source = @imagecreatefromstring((string) file_get_contents($sourcePath));
        if (! $source) throw new RuntimeException('No se pudo crear la vista previa.');
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 7, 27, 53));
        [$sourceX, $sourceY, $sourceWidth, $sourceHeight] = $this->crop(
            imagesx($source), imagesy($source)
        );
        imagecopyresampled(
            $canvas, $source, 0, 0, $sourceX, $sourceY,
            self::WIDTH, self::HEIGHT, $sourceWidth, $sourceHeight
        );
        $disk->makeDirectory(dirname($previewPath));
        if (! imagejpeg($canvas, $absolutePreview, 88)) {
            throw new RuntimeException('No se pudo guardar la vista previa.');
        }
        imagedestroy($source);
        imagedestroy($canvas);

        return $absolutePreview;
    }

    private function sourcePath(Property $property): string
    {
        $media = $property->media->firstWhere('is_cover', true)
            ?? $property->media->firstWhere('type', 'image');
        if ($media && config("filesystems.disks.{$media->disk}.driver") === 'local') {
            $path = Storage::disk($media->disk)->path($media->path);
            if (is_file($path)) return $path;
        }
        $imagePath = parse_url((string) $property->image_url, PHP_URL_PATH);
        if ($imagePath) {
            $path = str_starts_with($imagePath, '/storage/')
                ? storage_path('app/public/'.substr($imagePath, 9))
                : public_path(ltrim($imagePath, '/'));
            if (is_file($path)) return $path;
        }

        return public_path('og-blue-red.png');
    }

    private function crop(int $width, int $height): array
    {
        $targetRatio = self::WIDTH / self::HEIGHT;
        if ($width / $height > $targetRatio) {
            $cropWidth = (int) round($height * $targetRatio);
            return [(int) (($width - $cropWidth) / 2), 0, $cropWidth, $height];
        }
        $cropHeight = (int) round($width / $targetRatio);

        return [0, (int) (($height - $cropHeight) / 2), $width, $cropHeight];
    }
}
