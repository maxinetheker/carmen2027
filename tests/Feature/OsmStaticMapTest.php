<?php

namespace Tests\Feature;

use App\Services\Brochure\OsmStaticMap;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The server builds the croquis reference map itself from OpenStreetMap tiles, which is
 * what makes it automatic: no API key, no billing account, and no screen-share permission
 * (browsers always require the user to approve that, so a capture can never be silent).
 */
class OsmStaticMapTest extends TestCase
{
    public function test_a_map_image_is_built_from_coordinates_alone(): void
    {
        $this->fakeTiles();

        $png = app(OsmStaticMap::class)->png(-12.0464, -77.0428);

        $this->assertNotNull($png);
        [$width, $height] = getimagesizefromstring($png);
        $this->assertSame(768, $width);
        $this->assertSame(768, $height);
        // OSM's usage policy requires traffic to identify itself.
        Http::assertSent(fn ($request) => str_contains($request->header('User-Agent')[0] ?? '', 'CarmenMestanza'));
    }

    public function test_cached_tiles_are_text_safe_for_the_database_cache_driver(): void
    {
        // Regression: raw PNG bytes went straight into the cache, and the database driver
        // stores values in a utf8mb4 text column. MySQL refused them outright with
        // "Incorrect string value: '\x89PNG...'", so every real generation died. The test
        // suite uses the array driver and never saw it — hence this explicit check.
        $this->fakeTiles();

        app(OsmStaticMap::class)->png(-12.0464, -77.0428);

        $cached = Cache::get('osm-tile:16:18742:34977');
        $this->assertNotEmpty($cached);
        $this->assertSame($cached, base64_encode((string) base64_decode($cached, true)));
        $this->assertTrue(mb_check_encoding($cached, 'UTF-8'));
    }

    public function test_tiles_are_cached_so_a_second_brochure_downloads_nothing(): void
    {
        $this->fakeTiles();
        $map = app(OsmStaticMap::class);

        $map->png(-12.0464, -77.0428);
        $first = count(Http::recorded());
        $map->png(-12.0464, -77.0428);

        $this->assertSame(9, $first);
        $this->assertCount($first, Http::recorded());
    }

    public function test_a_failed_download_is_not_cached_as_a_permanent_hole(): void
    {
        Http::fake(['tile.openstreetmap.org/*' => Http::response('', 503)]);

        $this->assertNull(app(OsmStaticMap::class)->png(-12.0464, -77.0428));
        $this->assertNull(Cache::get('osm-tile:16:18742:34977'));
    }

    private function fakeTiles(): void
    {
        $image = imagecreatetruecolor(256, 256);
        imagefill($image, 0, 0, imagecolorallocate($image, 240, 238, 232));
        ob_start();
        imagepng($image);
        $tile = (string) ob_get_clean();
        imagedestroy($image);

        Http::fake(['tile.openstreetmap.org/*' => Http::response($tile, 200)]);
    }
}
