<?php

namespace Tests\Unit;

use App\Services\ImageBorderCropper;
use PHPUnit\Framework\TestCase;

class ImageBorderCropperTest extends TestCase
{
    public function test_it_removes_wide_white_side_borders(): void
    {
        $image = imagecreatetruecolor(100, 50);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagefilledrectangle($image, 30, 0, 69, 49, imagecolorallocate($image, 20, 80, 140));

        $cropped = (new ImageBorderCropper)->trimWhiteSides($image);

        $this->assertSame(40, imagesx($cropped));
        $this->assertSame(50, imagesy($cropped));
        imagedestroy($cropped);
    }

    public function test_it_preserves_an_image_without_white_side_borders(): void
    {
        $image = imagecreatetruecolor(100, 50);
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 80, 140));

        $sameImage = (new ImageBorderCropper)->trimWhiteSides($image);

        $this->assertSame(100, imagesx($sameImage));
        imagedestroy($sameImage);
    }
}
