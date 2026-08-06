<?php

namespace App\Services\Brochure;

use Illuminate\Support\Facades\Storage;

/**
 * Turns stored images into small base64 data URIs, so OpenAI's vision input never
 * depends on the app being publicly reachable (works the same on localhost or prod).
 */
class VisionImageEncoder
{
    public function fromDisk(string $disk, string $path, int $maxSide = 512): string
    {
        return $this->encode(Storage::disk($disk)->get($path), $maxSide);
    }

    public function encode(string $bytes, int $maxSide = 512): string
    {
        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            return 'data:image/jpeg;base64,'.base64_encode($bytes);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSide / max($width, $height));
        $targetW = max(1, (int) round($width * $scale));
        $targetH = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetW, $targetH, $width, $height);

        ob_start();
        imagewebp($resized, null, 70);
        $data = (string) ob_get_clean();

        imagedestroy($image);
        imagedestroy($resized);

        return 'data:image/webp;base64,'.base64_encode($data);
    }
}
