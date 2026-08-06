<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_android_can_edit_all_property_content_and_manage_media(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $html = '<h2>Visita pensada para ti 🏡</h2><p><strong>𝗧𝗲𝘅𝘁𝗼 original</strong><br>Segunda línea ✨</p>';
        $response = $this->postJson('/api/properties', [
            'title' => 'Casa desde Android',
            'code' => 'APP-001',
            'district' => 'Miraflores',
            'type' => 'casa',
            'operation' => 'venta',
            'status' => 'available',
            'price' => 320000,
            'currency' => 'USD',
            'area' => 180,
            'bedrooms' => 3,
            'bathrooms' => 2.5,
            'priority' => 90,
            'description' => $html,
            'image_url' => '/storage/properties/anterior.jpg',
            'features' => [[
                'icon' => 'garage', 'label' => 'Cochera', 'value' => '2',
            ]],
            'youtube_videos' => [[
                'original_url' => 'https://youtu.be/dQw4w9WgXcQ',
                'title' => 'Recorrido',
            ]],
        ])->assertCreated();

        $property = Property::where('code', 'APP-001')->firstOrFail();
        $normalizedHtml = '<h2>Visita pensada para ti 🏡</h2><p><strong>Texto original</strong><br>Segunda línea ✨</p>';
        $response->assertJsonPath('data.description', $normalizedHtml)
            ->assertJsonPath('data.image_url', '/storage/properties/anterior.jpg');
        $this->assertDatabaseHas('property_features', [
            'property_id' => $property->id, 'label' => 'Cochera', 'value' => '2',
        ]);
        $this->assertDatabaseHas('property_youtube_videos', [
            'property_id' => $property->id, 'youtube_id' => 'dQw4w9WgXcQ',
        ]);

        $first = $this->post("/api/properties/{$property->id}/media", [
            'media' => UploadedFile::fake()->image('frente.jpg', 1200, 800),
        ])->assertCreated()->json('data');
        $second = $this->post("/api/properties/{$property->id}/media", [
            'media' => UploadedFile::fake()->image('sala.png', 900, 700),
        ])->assertCreated()->json('data');
        $video = $this->post("/api/properties/{$property->id}/media", [
            'media' => UploadedFile::fake()->create('recorrido.mp4', 900, 'video/mp4'),
        ])->assertCreated()->json('data');

        $this->putJson("/api/properties/{$property->id}/media/order", [
            'order' => [$video['id'], $second['id'], $first['id']],
            'cover_media_id' => $second['id'],
        ])->assertOk()->assertJsonPath('data.0.id', $video['id']);
        $this->assertTrue($property->media()->findOrFail($second['id'])->is_cover);

        $this->delete("/api/properties/{$property->id}/media/{$second['id']}")->assertOk();
        $this->assertTrue($property->media()->where('type', 'image')->firstOrFail()->is_cover);
    }
}
