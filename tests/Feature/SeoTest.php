<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_expose_search_metadata_and_sitemap(): void
    {
        $this->seed();
        $property = Property::firstOrFail();

        $this->get(route('home'))->assertOk()
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:type" content="website"', false)
            ->assertSee('property="og:image" content="'.url('/og-blue-red.png').'"', false)
            ->assertSee('property="og:image:width" content="1731"', false)
            ->assertSee('property="og:image:height" content="909"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('Carmen Mestanza · Tu asesora inmobiliaria de confianza en Lima');
        $this->get(route('properties.show', $property))->assertOk()
            ->assertSee('og:type" content="product', false)
            ->assertSee('property="og:title" content="'.$property->title.' · Carmen Mestanza"', false)
            ->assertSee('property="og:image" content="'.route('properties.share-image', $property).'"', false)
            ->assertSee('property="og:image:type" content="image/jpeg"', false)
            ->assertSee('property="og:image:width" content="1200"', false)
            ->assertSee('property="og:image:height" content="630"', false)
            ->assertSee($property->district, false)
            ->assertSee('"@context":"https://schema.org"', false)
            ->assertSee('RealEstateListing', false)
            ->assertSee('numberOfBedrooms', false)->assertSee('GeoCoordinates', false)
            ->assertDontSee('<?php', false);
        $this->get(route('properties.share-image', $property))->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('Cache-Control', 'max-age=86400, public, stale-while-revalidate=604800');
        $this->get(route('sitemap'))->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('properties.show', $property), false);
    }

    public function test_admin_can_update_seo_content_and_share_image(): void
    {
        $this->seed();
        Storage::fake('public');

        $this->actingAs(User::first())->get(route('admin.settings.edit'))
            ->assertOk()->assertSee('data-seo-drop', false)
            ->assertSee('<iframe', false)->assertSee('sandbox', false);

        $this->actingAs(User::first())->put(route('admin.settings.update'), [
            'seo_title' => 'Propiedades en Lima · Carmen Mestanza',
            'seo_description' => 'Compra y venta de propiedades seleccionadas en Lima.',
            'seo_image' => UploadedFile::fake()->image('compartir.jpg', 1200, 630),
        ])->assertSessionHas('success');

        $path = ltrim(str_replace('/storage/', '', SiteSetting::where('key', 'seo_image_path')->value('value')), '/');
        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('site_settings', [
            'key' => 'seo_title', 'value' => 'Propiedades en Lima · Carmen Mestanza',
        ]);
    }
}
