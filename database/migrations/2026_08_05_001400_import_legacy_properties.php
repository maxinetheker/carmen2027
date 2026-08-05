<?php

use App\Models\Property;
use App\Support\LegacyPropertyImporter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration {
    public function up(): void
    {
        app(LegacyPropertyImporter::class)->import();
    }

    public function down(): void
    {
        Property::whereIn('code', LegacyPropertyImporter::CODES)->each(function (Property $property): void {
            Storage::disk('public')->deleteDirectory("properties/{$property->id}");
            $property->delete();
        });
    }
};
