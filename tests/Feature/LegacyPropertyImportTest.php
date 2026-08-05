<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Support\LegacyPropertyImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyPropertyImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_historical_properties_are_imported_by_the_migration_with_content_and_media(): void
    {
        $this->assertSame(7, Property::whereIn('code', LegacyPropertyImporter::CODES)->count());

        $property = Property::where('code', 'CM-008')->with(['media', 'features'])->firstOrFail();
        $this->assertSame('Chilca', $property->district);
        $this->assertSame('terreno', $property->type);
        $this->assertNotEmpty($property->description);
        $this->assertNotEmpty($property->features);
        $this->assertCount(13, $property->media);
        $this->assertTrue($property->media->first()->is_cover);
        $this->assertStringStartsWith('/storage/properties/', $property->media->first()->url);
        $this->assertSame('video', $property->media->last()->type);
        $this->assertTrue(Storage::disk('public')->exists($property->media->first()->path));
    }

    public function test_the_site_seeder_does_not_recreate_a_deleted_historical_property(): void
    {
        Property::where('code', 'CM-020')->delete();
        $this->seed();

        $this->assertDatabaseMissing('properties', ['code' => 'CM-020']);
    }
}
