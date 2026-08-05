<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('code')->unique();
            $table->string('type');
            $table->string('operation');
            $table->string('district');
            $table->string('address')->nullable();
            $table->decimal('price', 14, 2);
            $table->string('currency', 3)->default('USD');
            $table->unsignedTinyInteger('bedrooms')->default(0);
            $table->decimal('bathrooms', 4, 1)->default(0);
            $table->decimal('area', 10, 2);
            $table->string('status')->default('available');
            $table->boolean('featured')->default(false);
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('stage')->default('qualified');
            $table->decimal('value', 14, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedTinyInteger('probability')->default(25);
            $table->date('expected_close')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['stage', 'expected_close']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
        Schema::dropIfExists('properties');
    }
};
