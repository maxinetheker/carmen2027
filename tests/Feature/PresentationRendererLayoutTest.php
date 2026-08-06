<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Services\Brochure\PresentationRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PresentationRendererLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_long_ai_content_stays_within_the_planned_pages(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('tests/home.jpg', $this->imageBytes());
        $property = Property::create([
            'title' => 'Venta de Casa de 167 m² a Dos Cuadras de la Panamericana Norte',
            'code' => 'CM-LAYOUT', 'district' => 'Lima Norte', 'type' => 'casa', 'operation' => 'venta',
            'status' => 'available', 'price' => 280000, 'currency' => 'USD', 'area' => 167,
            'bedrooms' => 4, 'bathrooms' => 3, 'priority' => 0, 'slug' => 'casa-layout-test',
            'description' => str_repeat('Descripción detallada de ambientes y beneficios reales. ', 30),
        ]);
        foreach (range(0, 5) as $position) {
            $property->media()->create([
                'type' => 'image', 'disk' => 'public', 'path' => 'tests/home.jpg', 'sort_order' => $position,
            ]);
        }
        $property->load(['media', 'features']);
        $ids = $property->media->pluck('id')->all();
        $content = [
            'hook' => str_repeat('Dato atractivo de la propiedad. ', 10),
            'cards' => array_fill(0, 3, ['title' => str_repeat('Beneficio ', 8), 'description' => str_repeat('Detalle útil. ', 20)]),
            'quote' => str_repeat('Mensaje comercial. ', 10),
            'trust_paragraph' => '<p>'.str_repeat('Información verificada. ', 30).'</p>',
            'stats' => array_fill(0, 4, ['value' => '100%', 'label' => str_repeat('Dato de zona. ', 8)]),
        ];
        $faqs = array_fill(0, 7, ['question' => str_repeat('Pregunta ', 12), 'answer' => str_repeat('Respuesta detallada. ', 18)]);
        $result = app(PresentationRenderer::class)->render($property, [
            'template_key' => 'plantilla-1', 'max_pages' => 3,
        ], [
            'title' => $property->title, 'media_ids' => $ids, 'cover_media_id' => $ids[0],
            'interest' => $content, 'faqs' => $faqs, 'croquis_svg' => null, 'logo_key' => null, 'page_count' => 3,
        ]);

        $this->assertSame(3, $result['page_count']);
    }

    public function test_balanced_pages_keep_the_footer_without_creating_a_blank_page(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('tests/home.jpg', $this->imageBytes());
        $property = Property::create([
            'title' => 'Casa compacta para una visita privada', 'code' => 'CM-BALANCED',
            'district' => 'Ventanilla', 'type' => 'casa', 'operation' => 'venta',
            'status' => 'available', 'price' => 180000, 'currency' => 'USD', 'area' => 120,
            'bedrooms' => 3, 'bathrooms' => 2, 'priority' => 0, 'slug' => 'casa-balanced',
            'description' => 'Casa con ambientes funcionales y acceso a servicios cercanos.',
        ]);
        foreach (range(0, 4) as $position) {
            $property->media()->create([
                'type' => 'image', 'disk' => 'public', 'path' => 'tests/home.jpg', 'sort_order' => $position,
            ]);
        }
        $property->load(['media', 'features']);
        $ids = $property->media->pluck('id')->all();

        $result = app(PresentationRenderer::class)->render($property, [
            'template_key' => 'plantilla-1', 'max_pages' => 3,
        ], [
            'title' => $property->title, 'media_ids' => $ids, 'cover_media_id' => $ids[0],
            'interest' => ['hook' => 'Lista para conocer.', 'cards' => []],
            'faqs' => [['question' => '¿Cómo coordino una visita?', 'answer' => 'La asesora puede confirmar una fecha disponible.']],
            'croquis_svg' => null, 'logo_key' => null, 'page_count' => 3,
        ]);

        $this->assertSame(3, $result['page_count']);
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
