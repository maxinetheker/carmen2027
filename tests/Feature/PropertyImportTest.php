<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use App\Services\Import\RemaxPropertyParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PropertyImportTest extends TestCase
{
    use RefreshDatabase;

    private function html(): string
    {
        return file_get_contents(base_path('tests/fixtures/remax-property.html'));
    }

    public function test_it_reads_the_fields_of_a_remax_listing(): void
    {
        $parsed = app(RemaxPropertyParser::class)->parse($this->html(), 'https://www.remax.pe/x');

        $this->assertSame('Se Vende Casa En Av Separadora Industrial', $parsed['title']);
        $this->assertSame('casa', $parsed['type']);
        $this->assertSame('venta', $parsed['operation']);
        $this->assertSame('Ate', $parsed['district']);
        $this->assertSame('USD', $parsed['currency']);
        $this->assertSame(300000.0, $parsed['price']);
        $this->assertSame(1134000.0, $parsed['price_pen']);
        $this->assertSame(-12.0621245, $parsed['latitude']);
        $this->assertSame(-76.9549717, $parsed['longitude']);
        $this->assertNotEmpty($parsed['images']);
        $this->assertStringStartsWith('https://', $parsed['images'][0]);
        $this->assertNotEmpty($parsed['description']);
    }

    public function test_commercial_listings_are_typed_as_local(): void
    {
        $parser = app(RemaxPropertyParser::class);
        $badge = fn (string $text) => '<html><body><span class="badge badge-blue">'.$text.'</span>'
            .'<div class="titulo_01"><h1>Ficha</h1></div>'
            .'<div class="titulo_04"><ul><li>USD 100,000.00</li></ul></div></body></html>';

        $this->assertSame('local', $parser->parse($badge('LOCAL EN VENTA'))['type']);
        $this->assertSame('local', $parser->parse($badge('PROPIEDAD INDUSTRIAL EN VENTA'))['type']);
        $this->assertSame('oficina', $parser->parse($badge('OFICINA EN ALQUILER'))['type']);
        $this->assertSame('departamento', $parser->parse($badge('DEPARTAMENTO EN VENTA'))['type']);
    }

    public function test_pasted_html_is_previewed_without_touching_the_portal(): void
    {
        Http::fake();
        $this->seed();

        $response = $this->actingAs(User::firstOrFail())
            ->postJson(route('admin.properties.import.preview'), ['html' => $this->html()]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Se Vende Casa En Av Separadora Industrial')
            ->assertJsonPath('data.operation', 'venta');
        Http::assertNothingSent();
    }

    public function test_a_blocked_portal_explains_the_paste_fallback(): void
    {
        $this->seed();
        Http::fake(['*' => Http::response('<html><title>Just a moment...</title></html>', 403)]);

        $response = $this->actingAs(User::firstOrFail())
            ->postJson(route('admin.properties.import.preview'), [
                'url' => 'https://www.remax.pe/web/search/property/propiedad-casa-en-venta-ate-lima-lima-1081038/',
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString(
            'Pegar el código de la página', $response->json('message')
        );
    }

    public function test_import_creates_an_unpublished_property_with_its_photos(): void
    {
        $this->seed();
        Http::fake(['*' => Http::response(
            file_get_contents(base_path('tests/fixtures/pixel.png')), 200, ['Content-Type' => 'image/png']
        )]);

        $response = $this->actingAs(User::firstOrFail())
            ->postJson(route('admin.properties.import.store'), [
                'title' => 'Casa importada', 'district' => 'Ate', 'type' => 'casa',
                'operation' => 'venta', 'currency' => 'USD', 'price' => 300000,
                'area' => 180, 'bedrooms' => 3, 'bathrooms' => 2.5,
                'description' => 'Descripción traída del portal.',
                'features' => [['icon' => 'square_foot', 'label' => 'Área Terreno', 'value' => '180.00 m2']],
                'images' => ['https://example.test/foto-1.png', 'https://example.test/foto-2.png'],
                'source_url' => 'https://www.remax.pe/x',
            ]);

        $response->assertCreated();
        $property = Property::where('title', 'Casa importada')->firstOrFail();
        $this->assertFalse((bool) $property->is_published, 'Se importa sin publicar para revisarla antes.');
        $this->assertSame(2, $property->media()->count());
        $this->assertTrue((bool) $property->media()->first()->is_cover);
        $this->assertSame(1, $property->features()->count());
        Http::assertSentCount(2);
        $this->assertTrue(collect(Http::recorded())->every(
            fn (array $pair) => $pair[0] instanceof Request
        ));
    }
}
