<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyCodeAndMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_next_code_continues_the_correlative_and_skips_used_ones(): void
    {
        // A migration imports the historical CM-008…CM-020 properties; clear them so the
        // sequence is asserted from a known starting point.
        Property::query()->each(fn (Property $item) => $item->delete());

        $this->assertSame('CM-001', Property::nextCode());

        $this->property(['code' => 'CM-007']);
        $this->assertSame('CM-008', Property::nextCode());

        // A legacy code with no digits must not break the sequence.
        $this->property(['code' => 'LEGACY', 'title' => 'Antigua', 'slug' => 'antigua']);
        $this->assertSame('CM-008', Property::nextCode());
    }

    public function test_the_create_form_shows_the_code_as_read_only(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.properties.create'));

        $response->assertOk()
            ->assertSee('Se asignará automáticamente al guardar.')
            ->assertSee('readonly', false);
        // The code must not be a writable field the browser would post back.
        $this->assertStringNotContainsString('name="code"', $response->getContent());
    }

    public function test_updating_a_property_never_changes_its_code(): void
    {
        $property = $this->property(['code' => 'CM-042']);

        $this->actingAs($this->admin())
            ->put(route('admin.properties.update', $property), $this->payload(['code' => 'HACKED']))
            ->assertRedirect();

        $this->assertSame('CM-042', $property->fresh()->code);
    }

    public function test_gallery_files_upload_one_request_at_a_time_without_a_count_limit(): void
    {
        Storage::fake('public');
        $property = $this->property();
        $admin = $this->admin();

        // Comfortably past the old max:16 batch cap.
        foreach (range(1, 20) as $index) {
            $this->actingAs($admin)->post(route('admin.properties.media.store', $property), [
                'file' => UploadedFile::fake()->image("foto-{$index}.jpg", 900, 700),
            ])->assertCreated()->assertJsonStructure(['id', 'type', 'url', 'name']);
        }

        $this->assertCount(20, $property->fresh()->media);
        $this->assertCount(1, $property->fresh()->media->where('is_cover', true));
    }

    public function test_a_rejected_file_reports_its_own_reason(): void
    {
        Storage::fake('public');
        $property = $this->property();

        $this->actingAs($this->admin())
            ->postJson(route('admin.properties.media.store', $property), [
                'file' => UploadedFile::fake()->create('contrato.pdf', 120, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.file.0', 'Formato no admitido. Usa JPG, PNG, WebP, AVIF, MP4, WebM o MOV.');

        $this->assertCount(0, $property->fresh()->media);
    }

    public function test_deleting_the_cover_promotes_another_image(): void
    {
        Storage::fake('public');
        $property = $this->property();
        $admin = $this->admin();

        foreach (range(1, 2) as $index) {
            $this->actingAs($admin)->post(route('admin.properties.media.store', $property), [
                'file' => UploadedFile::fake()->image("foto-{$index}.jpg", 900, 700),
            ])->assertCreated();
        }
        $cover = $property->fresh()->media->firstWhere('is_cover', true);

        $this->actingAs($admin)
            ->delete(route('admin.properties.media.destroy', [$property, $cover]))
            ->assertOk();

        $remaining = $property->fresh()->media;
        $this->assertCount(1, $remaining);
        $this->assertTrue((bool) $remaining->first()->is_cover);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Carmen', 'email' => 'carmen@test.local', 'password' => bcrypt('secret-123'),
        ]);
    }

    private function property(array $overrides = []): Property
    {
        return Property::create($this->payload($overrides) + ['slug' => 'depa-'.uniqid()]);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Departamento de prueba', 'code' => 'CM-001', 'district' => 'Miraflores',
            'type' => 'departamento', 'operation' => 'venta', 'status' => 'available',
            'price' => 250000, 'currency' => 'USD', 'area' => 110, 'bedrooms' => 2,
            'bathrooms' => 2, 'priority' => 50,
        ];
    }
}
