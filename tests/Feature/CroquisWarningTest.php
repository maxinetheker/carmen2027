<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Services\Brochure\CroquisGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The croquis used to return null on every give-up path, so a brochure came back without
 * one and nothing anywhere said why. These cover the reasons an advisor can act on.
 */
class CroquisWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_property_without_coordinates_or_a_capture_explains_itself(): void
    {
        $result = $this->generate($this->property(), ['croquis_mode' => 'auto']);

        $this->assertNull($result['svg']);
        $this->assertStringContainsString('no tiene ubicación marcada', $result['warning']);
    }

    public function test_coordinates_without_a_capture_point_at_the_capture_button(): void
    {
        $property = $this->property(['latitude' => -12.0464, 'longitude' => -77.0428]);

        $result = $this->generate($property, ['croquis_mode' => 'auto']);

        $this->assertNull($result['svg']);
        $this->assertStringContainsString('Capturar mapa para la IA', $result['warning']);
    }

    public function test_no_google_api_key_is_read_or_required_anywhere(): void
    {
        // Coordinates alone used to trigger a server-side Static Maps download. The key
        // is gone, so nothing may look it up and no request may leave the server.
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        $property = $this->property(['latitude' => -12.0464, 'longitude' => -77.0428]);

        $this->generate($property, ['croquis_mode' => 'auto']);

        $this->assertNull(config('services.google_maps'));
    }

    public function test_a_disabled_croquis_is_not_reported_as_a_problem(): void
    {
        $result = $this->generate($this->property(), ['croquis_mode' => 'off']);

        $this->assertNull($result['svg']);
        $this->assertNull($result['warning']);
    }

    public function test_the_panel_embeds_google_maps_when_the_property_has_coordinates(): void
    {
        $property = $this->property(['latitude' => -12.0464, 'longitude' => -77.0428]);

        $this->actingAs(\App\Models\User::factory()->create())
            ->get(route('admin.properties.presentations.panel', $property))
            ->assertOk()
            ->assertSee('data-croquis-frame', false)
            ->assertSee('data-croquis-capture', false)
            ->assertSee('google.com/maps?q=-12.0464,-77.0428', false);
    }

    public function test_the_panel_asks_for_a_location_when_there_is_none(): void
    {
        $this->actingAs(\App\Models\User::factory()->create())
            ->get(route('admin.properties.presentations.panel', $this->property()))
            ->assertOk()
            ->assertSee('no tiene ubicación marcada')
            ->assertDontSee('data-croquis-frame', false);
    }

    private function generate(Property $property, array $options): array
    {
        return app(CroquisGenerator::class)->generate($property, $options, '#c9a227');
    }

    private function property(array $overrides = []): Property
    {
        return Property::create($overrides + [
            'title' => 'Casa de prueba', 'code' => 'CM-CROQUIS', 'slug' => 'casa-croquis',
            'district' => 'Miraflores', 'type' => 'casa', 'operation' => 'venta',
            'status' => 'available', 'price' => 250000, 'currency' => 'USD', 'area' => 110,
            'bedrooms' => 3, 'bathrooms' => 2, 'priority' => 50,
        ]);
    }
}
