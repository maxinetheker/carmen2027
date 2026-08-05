<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageOptimizer
{
    private const MAX_SIDE = 2400;
    private const QUALITY = 82;

    public function __construct(private readonly ImageBorderCropper $cropper) {}

    public function store(UploadedFile $file, int $propertyId): array
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $source) {
            throw new RuntimeException('No se pudo procesar la imagen.');
        }
        $source = $this->orient($source, $file);
        $source = $this->cropper->trimWhiteSides($source);
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_SIDE / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        imagecopyresampled(
            $target, $source, 0, 0, 0, 0,
            $targetWidth, $targetHeight, $width, $height
        );

        $path = "properties/{$propertyId}/images/".Str::uuid().'.webp';
        Storage::disk('public')->makeDirectory(dirname($path));
        $absolute = Storage::disk('public')->path($path);
        if (! imagewebp($target, $absolute, self::QUALITY)) {
            throw new RuntimeException('No se pudo guardar la imagen optimizada.');
        }
        imagedestroy($source);
        imagedestroy($target);

        return [
            'path' => $path, 'mime_type' => 'image/webp',
            'width' => $targetWidth, 'height' => $targetHeight,
            'size_bytes' => filesize($absolute) ?: null,
        ];
    }

    private function orient(\GdImage $image, UploadedFile $file): \GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }
        $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
        if (! $angle) return $image;
        $rotated = imagerotate($image, $angle, 0);
        if ($rotated) {
            imagedestroy($image);
            return $rotated;
        }
        return $image;
    }
}
