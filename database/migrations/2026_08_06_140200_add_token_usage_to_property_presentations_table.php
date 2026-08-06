<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('property_presentations', function (Blueprint $table) {
            $table->unsignedInteger('input_tokens')->default(0)->after('ai_content');
            $table->unsignedInteger('output_tokens')->default(0)->after('input_tokens');
            $table->unsignedInteger('cached_tokens')->default(0)->after('output_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('property_presentations', function (Blueprint $table) {
            $table->dropColumn(['input_tokens', 'output_tokens', 'cached_tokens']);
        });
    }
};
