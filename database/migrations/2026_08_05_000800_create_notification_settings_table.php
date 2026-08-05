<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email');
            $table->string('timezone')->default('America/Lima');
            $this->reminderColumns($table, 'follow_up', 7);
            $this->reminderColumns($table, 'appointment', 7);
            $this->reminderColumns($table, 'task', 1);
            $table->timestamps();
        });
    }

    private function reminderColumns(Blueprint $table, string $prefix, int $days): void
    {
        $table->boolean("{$prefix}_enabled")->default(true);
        $table->string("{$prefix}_frequency")->default('daily');
        $table->string("{$prefix}_time", 5)->default('08:00');
        $table->unsignedTinyInteger("{$prefix}_weekday")->default(1);
        $table->unsignedSmallInteger("{$prefix}_days")->default($days);
        $table->timestamp("{$prefix}_last_sent_at")->nullable();
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
