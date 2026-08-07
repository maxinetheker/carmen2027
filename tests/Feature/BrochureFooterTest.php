<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\SiteSetting;
use App\Services\Brochure\PresentationRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The footers used to be `display:table` boxes with a fixed `height`. CSS treats that
 * height as a *minimum* on a table and does not apply `overflow` to it, so long agent
 * details grew the panel until it ran off the bottom of the sheet: the .cta panel was
 * measured painting down to 309mm and .foot1 to 313mm on a 297mm page, slicing the
 * address line in half. dompdf still reports the expected page count in that state, so
 * this test looks at where ink actually lands instead.
 */
class BrochureFooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_brochure_content_is_painted_below_the_page(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('tests/home.jpg', $this->imageBytes());
        // Deliberately longer than anything the panel was designed around.
        SiteSetting::create(['key' => 'service_area', 'value' => 'Miraflores · San Isidro · Barranco · Surco · La Molina · San Borja, Jesús María, Pueblo Libre, Magdalena']);
        SiteSetting::create(['key' => 'ceo_title', 'value' => 'Agente Inmobiliario Certificado RE/MAX Integrity']);
        // Deliberately long contact details: footers size their own text to fit, so these
        // must shrink rather than wrap off the sheet — and must arrive intact, since a
        // truncated e-mail address is useless to whoever reads the brochure.
        SiteSetting::create(['key' => 'email', 'value' => 'carmen.mestanza.inmobiliaria.contacto@remaxintegrityperu.com.pe']);
        SiteSetting::create(['key' => 'phone', 'value' => '+51 925 081 702']);

        $property = Property::create([
            'title' => 'Local industrial I-2 de 1,005 m² en Villa El Salvador',
            'code' => 'CM-FOOTER', 'district' => 'Villa El Salvador', 'type' => 'terreno',
            'operation' => 'venta', 'status' => 'available', 'price' => 550000, 'currency' => 'USD',
            'area' => 1005, 'bedrooms' => 0, 'bathrooms' => 0, 'priority' => 0,
            'slug' => 'local-footer', 'description' => 'Local con zonificación I2 y buena altura libre.',
        ]);
        foreach (range(0, 4) as $position) {
            $property->media()->create([
                'type' => 'image', 'disk' => 'public', 'path' => 'tests/home.jpg', 'sort_order' => $position,
            ]);
        }
        $property->load(['media', 'features']);
        $ids = $property->media->pluck('id')->all();

        $result = app(PresentationRenderer::class)->render($property, [
            'template_key' => 'plantilla-2', 'max_pages' => 3,
        ], [
            'title' => $property->title, 'media_ids' => $ids, 'cover_media_id' => $ids[0],
            'interest' => ['hook' => 'Operación industrial inmediata.', 'cards' => []],
            'faqs' => [['question' => '¿Cuál es el precio?', 'answer' => 'USD 550,000 según la ficha.']],
            'croquis_svg' => null, 'logo_key' => null, 'page_count' => 3,
        ]);

        $lowest = $this->lowestPaintedPoint($result['pdf']);

        $this->assertGreaterThanOrEqual(
            -0.5,
            $lowest,
            "Se dibujó contenido {$lowest}pt por debajo del borde inferior de la hoja (el pie se está recortando)."
        );
        $this->assertStringContainsString(
            'carmen.mestanza.inmobiliaria.contacto@remaxintegrityperu.com.pe',
            app(\App\Services\Brochure\PropertyFacts::class)->agent()['email'],
            'El correo se está recortando; debe caber reduciendo el tamaño, no cortando el texto.'
        );
    }

    /**
     * Lowest y coordinate touched by a filled rectangle, in PDF points measured from the
     * bottom of the sheet. Anything below 0 has fallen off the page.
     */
    private function lowestPaintedPoint(string $pdf): float
    {
        $lowest = 0.0;
        foreach ($this->contentStreams($pdf) as $stream) {
            preg_match_all(
                '/(-?[\d.]+)\s+(-?[\d.]+)\s+(-?[\d.]+)\s+(-?[\d.]+)\s+re/',
                $stream,
                $matches,
                PREG_SET_ORDER
            );
            foreach ($matches as $match) {
                $y = (float) $match[2];
                $height = (float) $match[4];
                $lowest = min($lowest, $y, $y + $height);
            }
        }

        return round($lowest, 2);
    }

    /** @return string[] */
    private function contentStreams(string $pdf): array
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches);
        $streams = [];
        foreach ($matches[1] as $raw) {
            $decoded = @gzuncompress($raw);
            $streams[] = $decoded === false ? $raw : $decoded;
        }

        return $streams;
    }

    private function imageBytes(): string
    {
        $image = imagecreatetruecolor(1200, 800);
        imagefill($image, 0, 0, imagecolorallocate($image, 55, 96, 140));
        ob_start();
        imagejpeg($image, null, 80);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
