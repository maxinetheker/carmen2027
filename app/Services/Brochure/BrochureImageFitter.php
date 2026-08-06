<?php

namespace App\Services\Brochure;

use Illuminate\Support\Facades\Storage;

/**
 * dompdf does not support CSS `object-fit: cover`, so any `<img>` placed at a fixed
 * size would be stretched/deformed instead of cropped. This crops+resizes server-side
 * (same GD approach as ImageOptimizer) to the exact pixel box before embedding, so the
 * template's <img> tag never needs object-fit and can never look deformed.
 */
class BrochureImageFitter
{
    private const DPI = 96;

    /**
     * @return string a ready-to-embed `data:image/jpeg;base64,...` URI
     */
    public function fitMm(string $disk, string $path, float $widthMm, float $heightMm, int $quality = 78): string
    {
        return $this->fitPx(
            $disk, $path,
            (int) round($widthMm * self::DPI / 25.4),
            (int) round($heightMm * self::DPI / 25.4),
            $quality
        );
    }

    public function fitPx(string $disk, string $path, int $targetW, int $targetH, int $quality = 78): string
    {
        $bytes = Storage::disk($disk)->get($path);
        $source = @imagecreatefromstring($bytes);
        if (! $source) {
            return 'data:image/jpeg;base64,'.base64_encode($bytes);
        }

        $width = imagesx($source);
        $height = imagesy($source);

        $scale = max($targetW / $width, $targetH / $height);
        $scaledW = (int) ceil($width * $scale);
        $scaledH = (int) ceil($height * $scale);

        $scaled = imagecreatetruecolor($scaledW, $scaledH);
        imagecopyresampled($scaled, $source, 0, 0, 0, 0, $scaledW, $scaledH, $width, $height);

        $cropX = (int) max(0, round(($scaledW - $targetW) / 2));
        $cropY = (int) max(0, round(($scaledH - $targetH) / 2));

        $cropped = imagecreatetruecolor($targetW, $targetH);
        imagecopy($cropped, $scaled, 0, 0, $cropX, $cropY, $targetW, $targetH);

        ob_start();
        imagejpeg($cropped, null, $quality);
        $data = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($scaled);
        imagedestroy($cropped);

        return 'data:image/jpeg;base64,'.base64_encode($data);
    }

    /**
     * "Contain" fit for logos: preserves aspect ratio and transparency (unlike
     * fitMm/fitPx, which crop-to-fill photos) and never upscales past the source.
     *
     * @return string a ready-to-embed `data:image/png;base64,...` URI
     */
    public function fitContainMm(string $bytes, float $maxWidthMm, float $maxHeightMm): string
    {
        $maxW = (int) round($maxWidthMm * self::DPI / 25.4);
        $maxH = (int) round($maxHeightMm * self::DPI / 25.4);

        $source = @imagecreatefromstring($bytes);
        if (! $source) {
            return 'data:image/png;base64,'.base64_encode($bytes);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxW / $width, $maxH / $height);
        $targetW = max(1, (int) round($width * $scale));
        $targetH = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($targetW, $targetH);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetW, $targetH, $width, $height);

        ob_start();
        imagepng($resized);
        $data = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($resized);

        return 'data:image/png;base64,'.base64_encode($data);
    }
}
