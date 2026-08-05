<?php

namespace App\Services;

use GdImage;
use RuntimeException;

final class ImageBorderCropper
{
    private const WHITE = 245;
    private const CONTENT_RATIO = .04;

    public function trimWhiteSides(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $left = $this->contentEdge($image, 0, $width, 1, $height);
        $right = $this->contentEdge($image, $width - 1, -1, -1, $height);
        $cropWidth = $right - $left + 1;
        $removed = $width - $cropWidth;
        if ($removed < max(16, (int) round($width * .08)) || $cropWidth < $width * .2) {
            return $image;
        }

        $cropped = imagecrop($image, ['x' => $left, 'y' => 0, 'width' => $cropWidth, 'height' => $height]);
        if (! $cropped) return $image;
        imagedestroy($image);

        return $cropped;
    }

    public function trimWebpFile(string $path): array
    {
        $image = @imagecreatefromstring((string) file_get_contents($path));
        if (! $image) throw new RuntimeException('No se pudo analizar la imagen importada.');
        $originalWidth = imagesx($image);
        $image = $this->trimWhiteSides($image);
        $cropped = imagesx($image) !== $originalWidth;
        if ($cropped && ! imagewebp($image, $path, 82)) {
            imagedestroy($image);
            throw new RuntimeException('No se pudo guardar la imagen sin márgenes.');
        }
        $metrics = ['width' => imagesx($image), 'height' => imagesy($image), 'cropped' => $cropped];
        imagedestroy($image);

        return $metrics;
    }

    private function contentEdge(GdImage $image, int $start, int $end, int $step, int $height): int
    {
        $sampleStep = max(1, (int) floor($height / 160));
        $samples = (int) ceil($height / $sampleStep);
        for ($x = $start; $x !== $end; $x += $step) {
            $content = 0;
            for ($y = 0; $y < $height; $y += $sampleStep) {
                $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                if ($color['alpha'] < 120 && min($color['red'], $color['green'], $color['blue']) < self::WHITE) {
                    $content++;
                }
            }
            if ($content / max(1, $samples) >= self::CONTENT_RATIO) return $x;
        }

        return $start;
    }
}
