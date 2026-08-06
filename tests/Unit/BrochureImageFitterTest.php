<?php

namespace Tests\Unit;

use App\Services\Brochure\BrochureImageFitter;
use PHPUnit\Framework\TestCase;

class BrochureImageFitterTest extends TestCase
{
    private function pngBytes(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 200, 30, 30, 0));
        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    public function test_it_preserves_aspect_ratio_within_the_box(): void
    {
        // 1626x967 (~1.68 ratio, like the horizontal logos) into a 34x18mm box.
        $bytes = $this->pngBytes(1626, 967);

        $uri = (new BrochureImageFitter)->fitContainMm($bytes, 34, 18);

        $this->assertStringStartsWith('data:image/png;base64,', $uri);
        [$width, $height] = $this->decodedSize($uri);
        $this->assertEqualsWithDelta(1626 / 967, $width / $height, 0.02);
    }

    public function test_it_never_upscales_a_small_source(): void
    {
        // A tiny 20x20 source into a much bigger box must stay at its own size.
        $bytes = $this->pngBytes(20, 20);

        $uri = (new BrochureImageFitter)->fitContainMm($bytes, 34, 18);

        [$width, $height] = $this->decodedSize($uri);
        $this->assertSame(20, $width);
        $this->assertSame(20, $height);
    }

    private function decodedSize(string $dataUri): array
    {
        $binary = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1));
        $image = imagecreatefromstring($binary);

        return [imagesx($image), imagesy($image)];
    }
}
