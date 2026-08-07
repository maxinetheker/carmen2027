<?php

namespace App\Services\Brochure;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Builds a street-map picture of a coordinate on the server, with no API key.
 *
 * OpenStreetMap serves plain 256px raster tiles over HTTP, so a map is just the right
 * grid of tiles pasted together (the standard "slippy map" scheme). That makes the
 * croquis reference automatic: nothing to click, no browser permission, no billing
 * account — which a screen capture can never be, because browsers always require the
 * user to approve sharing. Tiles are cached so repeated brochures cost no downloads,
 * and the User-Agent identifies this app as OSM's usage policy requires.
 */
class OsmStaticMap
{
    private const TILE = 256;

    private const GRID = 3;

    public function png(float $latitude, float $longitude, ?int $zoom = null): ?string
    {
        $zoom = $zoom ?? (int) config('services.map_tiles.zoom', 16);
        [$worldX, $worldY] = $this->project($latitude, $longitude, $zoom);
        $originX = (int) floor($worldX) - intdiv(self::GRID, 2);
        $originY = (int) floor($worldY) - intdiv(self::GRID, 2);

        $size = self::TILE * self::GRID;
        $canvas = imagecreatetruecolor($size, $size);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 229, 227, 223));
        $placed = 0;

        for ($dx = 0; $dx < self::GRID; $dx++) {
            for ($dy = 0; $dy < self::GRID; $dy++) {
                $bytes = $this->tile($zoom, $originX + $dx, $originY + $dy);
                $tile = $bytes ? @imagecreatefromstring($bytes) : null;
                if (! $tile) {
                    continue;
                }
                imagecopy($canvas, $tile, $dx * self::TILE, $dy * self::TILE, 0, 0, self::TILE, self::TILE);
                imagedestroy($tile);
                $placed++;
            }
        }

        if ($placed < 3) {
            imagedestroy($canvas);
            Log::warning('No se pudieron descargar suficientes mosaicos de mapa para el croquis');

            return null;
        }

        $this->drawMarker(
            $canvas,
            (int) round(($worldX - $originX) * self::TILE),
            (int) round(($worldY - $originY) * self::TILE)
        );

        ob_start();
        imagepng($canvas);
        $png = (string) ob_get_clean();
        imagedestroy($canvas);

        return $png;
    }

    /** Longitude/latitude to fractional tile coordinates (Web Mercator). */
    private function project(float $latitude, float $longitude, int $zoom): array
    {
        $n = 2 ** $zoom;
        $latRad = deg2rad($latitude);

        return [
            ($longitude + 180) / 360 * $n,
            (1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2 * $n,
        ];
    }

    private function tile(int $zoom, int $x, int $y): ?string
    {
        $max = 2 ** $zoom;
        if ($x < 0 || $y < 0 || $x >= $max || $y >= $max) {
            return null;
        }

        // Base64, not the raw bytes: the database cache driver stores values in a utf8mb4
        // text column and MySQL rejects a PNG outright ("Incorrect string value:
        // '\x89PNG...'"). Encoding keeps the entry valid on every cache driver.
        $key = "osm-tile:{$zoom}:{$x}:{$y}";
        if ($cached = Cache::get($key)) {
            return base64_decode($cached) ?: null;
        }

        $url = str_replace(
            ['{z}', '{x}', '{y}'], [$zoom, $x, $y], (string) config('services.map_tiles.url')
        );

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => (string) config('services.map_tiles.agent')])
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('No se pudo descargar un mosaico de mapa', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        // Only successes are cached, so a transient outage does not stick for 30 days.
        Cache::put($key, base64_encode($response->body()), now()->addDays(30));

        return $response->body();
    }

    /** A red pin with a white outline, so the AI can see which point to highlight. */
    private function drawMarker($canvas, int $x, int $y): void
    {
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $red = imagecolorallocate($canvas, 214, 40, 40);

        imagefilledellipse($canvas, $x, $y, 26, 26, $white);
        imagefilledellipse($canvas, $x, $y, 20, 20, $red);
        imagefilledellipse($canvas, $x, $y, 8, 8, $white);
    }
}
