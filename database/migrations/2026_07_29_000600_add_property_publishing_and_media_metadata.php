<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_hero')->default(false);
            $table->unsignedSmallInteger('priority')->default(0);
        });

        Schema::table('property_media', function (Blueprint $table) {
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('property_media', function (Blueprint $table) {
            $table->dropColumn(['width', 'height', 'size_bytes']);
        });
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['is_published', 'show_in_hero', 'priority']);
        });
    }
};
