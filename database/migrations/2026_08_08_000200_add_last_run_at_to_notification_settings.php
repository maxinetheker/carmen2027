<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            // Marca de la última pasada del scheduler. Es la única forma de saber,
            // desde el panel, si el cron del hosting está corriendo cada minuto o
            // si solo se ejecuta una vez al día (que es lo que hacía que todos los
            // avisos llegaran juntos a las 8 de la mañana).
            $table->timestamp('last_run_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notification_settings', fn (Blueprint $table) => $table->dropColumn('last_run_at'));
    }
};
