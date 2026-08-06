<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PropertyPresentationSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_images_require_one_primary_image_from_the_selected_set(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $property = Property::create([
            'title' => 'Departamento en San Isidro', 'code' => 'CM-102', 'district' => 'San Isidro',
            'type' => 'departamento', 'operation' => 'venta', 'status' => 'available',
            'price' => 250000, 'currency' => 'USD', 'area' => 120, 'bedrooms' => 3, 'bathrooms' => 2,
            'priority' => 0, 'slug' => 'departamento-san-isidro-cm-102',
        ]);
        $selected = $property->media()->create([
            'type' => 'image', 'disk' => 'public', 'path' => 'properties/demo-a.jpg', 'sort_order' => 0,
        ]);
        $other = $property->media()->create([
            'type' => 'image', 'disk' => 'public', 'path' => 'properties/demo-b.jpg', 'sort_order' => 1,
        ]);
        $payload = [
            'template_key' => 'plantilla-1', 'logo_mode' => 'off', 'audience' => 'personas',
            'croquis_mode' => 'off', 'images_mode' => 'manual', 'selected_image_ids' => [$selected->id],
            'interest_mode' => 'off', 'faq_mode' => 'off', 'title_mode' => 'off', 'max_pages' => 3,
        ];

        $this->postJson(route('admin.properties.presentations.store', $property), $payload + [
            'cover_media_id' => $other->id,
        ])->assertStatus(422)->assertJsonValidationErrors('cover_media_id');

        $this->postJson(route('admin.properties.presentations.store', $property), $payload + [
            'cover_media_id' => $selected->id,
        ])->assertOk();
    }
}
