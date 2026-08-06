<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('queued');
            $table->string('template_key', 60);
            $table->string('pdf_disk', 30)->default('public');
            $table->string('pdf_path')->nullable();
            $table->unsignedTinyInteger('page_count')->nullable();
            $table->json('options');
            $table->json('ai_content')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_presentations');
    }
};
