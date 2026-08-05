<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_ceo_can_view_dashboard_and_manage_property(): void
    {
        $this->seed();
        $user = User::first();
        Storage::fake('public');

        $this->actingAs($user)->get('/admin')->assertOk()
            ->assertSee('Buenos días');
        $this->actingAs($user)->get(route('admin.deals.create'))
            ->assertOk()->assertSee('data-searchable-select', false);
        $this->actingAs($user)->get(route('admin.properties.create'))
            ->assertOk()
            ->assertSee('Descripción avanzada')
            ->assertSee('Imagen principal')
            ->assertSee('Agregar fotos o videos')
            ->assertSee('Características visuales')
            ->assertSee('Área de parrillas')
            ->assertSee('Walk-in closet')
            ->assertSee('data-location-picker', false);
        $response = $this->actingAs($user)->post(
            route('admin.properties.store'), [
                'title' => 'Departamento de prueba',
                'code' => 'TEST-1',
                'district' => 'Miraflores',
                'type' => 'departamento',
                'operation' => 'venta',
                'status' => 'available',
                'price' => 250000,
                'currency' => 'USD',
                'area' => 110,
                'bedrooms' => 2,
                'bathrooms' => 2,
                'latitude' => -12.1211,
                'longitude' => -77.0297,
                'featured' => 1,
                'is_published' => 1,
                'show_in_hero' => 1,
                'priority' => 90,
                'description' => '<h2>Vista al parque</h2><script>alert(1)</script><p>Lista para visitar.</p>',
                'cover_image' => UploadedFile::fake()->image('portada.jpg', 3200, 2000),
                'media_files' => [
                    UploadedFile::fake()->image('fachada.jpg', 900, 700),
                    UploadedFile::fake()->create('recorrido.mp4', 900, 'video/mp4'),
                ],
                'media_manifest' => json_encode(['new:0', 'new:1']),
                'features' => [[
                    'icon' => 'account_balance',
                    'label' => 'Registro',
                    'value' => 'Inscrito',
                ]],
                'youtube_videos' => [[
                    'url' => 'https://youtu.be/dQw4w9WgXcQ', 'title' => 'Recorrido virtual',
                ]],
            ]
        );
        $response->assertRedirect(route('admin.properties.index'));
        $this->assertDatabaseHas('properties', ['code' => 'TEST-1']);
        $property = Property::where('code', 'TEST-1')->firstOrFail();
        $this->assertSame(-12.1211, $property->latitude);
        $this->assertSame(-77.0297, $property->longitude);
        $this->assertCount(3, $property->media);
        $cover = $property->media->firstWhere('is_cover', true);
        $this->assertSame('image/webp', $cover->mime_type);
        $this->assertLessThanOrEqual(2400, max($cover->width, $cover->height));
        $this->assertStringEndsWith('.webp', $cover->path);
        $this->assertDatabaseHas('property_features', [
            'property_id' => $property->id, 'label' => 'Registro', 'value' => 'Inscrito',
        ]);
        $this->assertDatabaseHas('property_youtube_videos', [
            'property_id' => $property->id, 'youtube_id' => 'dQw4w9WgXcQ',
        ]);
        $this->assertStringNotContainsString('<script', $property->description);
        Storage::disk('public')->assertExists($cover->path);
        $this->actingAs($user)->get(route('admin.properties.edit', $property))
            ->assertOk()->assertSee('portada.jpg')->assertSee('Registro')
            ->assertSee('data-undo-changes', false);
        $this->get(route('properties.show', $property))->assertOk()
            ->assertSee('Inscrito')->assertSee('Vista al parque')
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);

        $galleryImage = $property->media->firstWhere('original_name', 'fachada.jpg');
        $video = $property->media->firstWhere('type', 'video');
        $this->actingAs($user)->put(route('admin.properties.update', $property), [
            'title' => $property->title, 'code' => $property->code,
            'district' => $property->district, 'type' => $property->type,
            'operation' => $property->operation, 'status' => $property->status,
            'price' => $property->price, 'currency' => $property->currency,
            'area' => $property->area, 'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms, 'priority' => 90,
            'image_url' => '/images/property-3.jpg',
            'is_published' => 1, 'cover_media_id' => $galleryImage->id,
            'media_manifest' => json_encode([
                'existing:'.$video->id, 'existing:'.$galleryImage->id,
                'existing:'.$cover->id,
            ]),
            'features' => [[
                'icon' => 'account_balance', 'label' => 'Registro', 'value' => 'Inscrito',
            ]],
        ])->assertRedirect(route('admin.properties.index'));
        $ordered = $property->fresh()->media;
        $this->assertSame('/images/property-3.jpg', $property->fresh()->image_url);
        $this->assertSame('recorrido.mp4', $ordered->first()->original_name);
        $this->assertTrue($ordered->firstWhere('original_name', 'fachada.jpg')->is_cover);
    }

    public function test_lead_can_be_converted_to_pipeline(): void
    {
        $this->seed();
        $lead = Lead::where('status', 'new')->firstOrFail();

        $this->actingAs(User::first())
            ->post(route('admin.leads.convert', $lead))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('deals', ['lead_id' => $lead->id]);
        $this->assertDatabaseHas('contacts', ['phone' => $lead->phone]);
    }
}
