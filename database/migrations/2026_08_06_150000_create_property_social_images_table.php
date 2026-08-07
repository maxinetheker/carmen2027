<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * History of AI-generated social-media images, mirroring property_presentations so the
 * advisor can go back to any post produced for a property instead of regenerating it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_social_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('queued');
            $table->string('format')->default('cuadrado');
            $table->string('quality')->default('media');
            $table->json('options')->nullable();
            $table->json('ai_content')->nullable();
            $table->string('image_disk')->nullable();
            $table->string('image_path')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cached_tokens')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_social_images');
    }
};
