<?php

namespace Tests\Feature;

use App\Jobs\GeneratePropertyPresentationJob;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function property(string $code = 'CM-100'): Property
    {
        return Property::create([
            'title' => 'Departamento en San Isidro', 'code' => $code, 'district' => 'San Isidro',
            'type' => 'departamento', 'operation' => 'venta', 'status' => 'available',
            'price' => 250000, 'currency' => 'USD', 'area' => 120, 'bedrooms' => 3, 'bathrooms' => 2,
            'priority' => 0, 'slug' => 'departamento-san-isidro-'.strtolower($code),
        ]);
    }

    public function test_generation_requires_a_valid_template_and_dispatches_the_job(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $property = $this->property();

        $this->postJson(route('admin.properties.presentations.store', $property), [
            'template_key' => 'no-existe',
            'logo_mode' => 'off',
            'images_mode' => 'auto',
            'interest_mode' => 'off',
            'faq_mode' => 'off',
            'title_mode' => 'off',
            'max_pages' => 3,
        ])->assertStatus(422)->assertJsonValidationErrors('template_key');

        Queue::assertNothingPushed();

        $this->postJson(route('admin.properties.presentations.store', $property), [
            'template_key' => 'plantilla-1',
            'logo_mode' => 'off',
            'images_mode' => 'auto',
            'interest_mode' => 'off',
            'faq_mode' => 'off',
            'title_mode' => 'off',
            'max_pages' => 3,
        ])->assertOk()->assertJsonPath('status', 'queued');

        $this->assertDatabaseHas('property_presentations', [
            'property_id' => $property->id, 'status' => 'queued', 'template_key' => 'plantilla-1',
        ]);
        Queue::assertPushed(GeneratePropertyPresentationJob::class);
    }

    public function test_manual_image_mode_requires_at_least_one_selected_image(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $property = $this->property();

        $this->postJson(route('admin.properties.presentations.store', $property), [
            'template_key' => 'plantilla-1',
            'logo_mode' => 'off',
            'images_mode' => 'manual',
            'interest_mode' => 'off',
            'faq_mode' => 'off',
            'title_mode' => 'off',
            'max_pages' => 3,
        ])->assertStatus(422)->assertJsonValidationErrors('selected_image_ids');
    }

    public function test_manual_logo_mode_requires_a_valid_logo_key(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());
        $property = $this->property();

        $this->postJson(route('admin.properties.presentations.store', $property), [
            'template_key' => 'plantilla-1',
            'logo_mode' => 'manual',
            'images_mode' => 'auto',
            'interest_mode' => 'off',
            'faq_mode' => 'off',
            'title_mode' => 'off',
            'max_pages' => 3,
        ])->assertStatus(422)->assertJsonValidationErrors('logo_key');

        $this->postJson(route('admin.properties.presentations.store', $property), [
            'template_key' => 'plantilla-1',
            'logo_mode' => 'manual',
            'logo_key' => 'symbol',
            'images_mode' => 'auto',
            'interest_mode' => 'off',
            'faq_mode' => 'off',
            'title_mode' => 'off',
            'max_pages' => 3,
        ])->assertOk();
    }

    public function test_destroy_removes_the_stored_pdf_and_the_record(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $property = $this->property();

        $path = "properties/{$property->id}/presentations/demo.pdf";
        Storage::disk('public')->put($path, '%PDF-1.4 fake');
        $presentation = $property->presentations()->create([
            'status' => 'done', 'template_key' => 'plantilla-1',
            'pdf_disk' => 'public', 'pdf_path' => $path,
            'options' => ['template_key' => 'plantilla-1'],
        ]);

        $this->delete(route('admin.properties.presentations.destroy', [$property, $presentation]))
            ->assertRedirect();

        $this->assertDatabaseMissing('property_presentations', ['id' => $presentation->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_status_endpoint_only_exposes_the_owning_propertys_presentation(): void
    {
        $this->actingAs(User::factory()->create());
        $property = $this->property();
        $otherProperty = $this->property('CM-101');

        $presentation = $otherProperty->presentations()->create([
            'status' => 'done', 'template_key' => 'plantilla-1', 'options' => [],
        ]);

        $this->getJson(route('admin.properties.presentations.status', [$property, $presentation]))
            ->assertNotFound();
    }
}
