<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('follow_up_status')->default('active')->after('last_contact_at');
            $table->timestamp('next_contact_at')->nullable()->after('follow_up_status');
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('follow_up_status')->default('active')->after('last_contact_at');
            $table->timestamp('next_contact_at')->nullable()->after('follow_up_status');
        });
    }

    public function down(): void
    {
        Schema::table('leads', fn (Blueprint $table) =>
            $table->dropColumn(['follow_up_status', 'next_contact_at']));
        Schema::table('contacts', fn (Blueprint $table) =>
            $table->dropColumn(['follow_up_status', 'next_contact_at']));
    }
};
