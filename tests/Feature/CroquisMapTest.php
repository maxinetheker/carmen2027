<?php

namespace Tests\Feature;

use App\Jobs\GeneratePropertyPresentationJob;
use App\Models\Property;
use App\Models\User;
use App\Services\Brochure\CroquisGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The croquis reference is built from the property's own coordinates, so the advisor has
 * nothing to click. The one unwinnable case — no location and no attached capture — is
 * refused during validation instead of after the AI calls have been paid for.
 */
class CroquisMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_map_is_fetched_from_coordinates_with_no_upload(): void
    {
        $this->fakeTiles();
        $property = $this->property(['latitude' => -12.0464, 'longitude' => -77.0428]);

        // No croquis_reference_path: the map is the automatic one. It gets as far as the
        // AI call, which is where this stops without an OpenAI key configured.
        try {
            app(CroquisGenerator::class)->generate($property, ['croquis_mode' => 'auto'], '#c9a227');
        } catch (\Throwable) {
            // Expected in the test environment.
        }

        Http::assertSent(fn ($request) => str_contains($request->url(), 'tile.openstreetmap.org'));
    }

    public function test_a_property_without_a_location_is_refused_before_anything_queues(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $property = $this->property();

        $this->postJson(route('admin.properties.presentations.store', $property), $this->generationOptions())
            ->assertStatus(422)
            ->assertJsonValidationErrors('croquis_mode');

        Queue::assertNothingPushed();
        $this->assertSame(0, $property->presentations()->count());
    }

    public function test_coordinates_alone_are_enough_to_start_a_generation(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $property = $this->property(['latitude' => -12.0464, 'longitude' => -77.0428]);

        $this->postJson(route('admin.properties.presentations.store', $property), $this->generationOptions())
            ->assertOk();

        Queue::assertPushed(GeneratePropertyPresentationJob::class);
    }

    public function test_a_disabled_croquis_is_never_treated_as_a_problem(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());

        $this->postJson(
            route('admin.properties.presentations.store', $this->property()),
            ['croquis_mode' => 'off'] + $this->generationOptions()
        )->assertOk();

        $result = app(CroquisGenerator::class)
            ->generate($this->property(['code' => 'CM-OFF', 'slug' => 'off']), ['croquis_mode' => 'off'], '#000');

        $this->assertNull($result['svg']);
        $this->assertNull($result['warning']);
    }

    public function test_the_panel_says_the_map_is_automatic(): void
    {
        $property = $this->property(['latitude' => -12.0464, 'longitude' => -77.0428]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.properties.presentations.panel', $property))
            ->assertOk()
            ->assertSee('El mapa se arma solo con la ubicación guardada')
            ->assertSee('data-croquis-capture', false);
    }

    public function test_no_google_api_key_is_read_anywhere(): void
    {
        $this->assertNull(config('services.google_maps'));
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

    private function generationOptions(array $overrides = []): array
    {
        return $overrides + [
            'template_key' => 'plantilla-1', 'logo_mode' => 'off', 'images_mode' => 'auto',
            'interest_mode' => 'off', 'faq_mode' => 'off', 'title_mode' => 'off',
            'max_pages' => 3, 'audience' => 'personas', 'croquis_mode' => 'auto',
        ];
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
