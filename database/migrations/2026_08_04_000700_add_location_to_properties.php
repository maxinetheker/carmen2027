<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->index(['latitude', 'longitude']);
        });

        foreach ([
            'Miraflores' => [-12.1211000, -77.0297000],
            'San Isidro' => [-12.0970000, -77.0369000],
            'Barranco' => [-12.1490000, -77.0208000],
            'Santiago de Surco' => [-12.1416000, -76.9918000],
            'La Molina' => [-12.0820000, -76.9282000],
        ] as $district => [$latitude, $longitude]) {
            DB::table('properties')->where('district', $district)
                ->whereNull('latitude')->update(compact('latitude', 'longitude'));
        }
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
