<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->text('recipient_emails')->nullable()->after('recipient_email');
            $table->boolean('appointment_immediate_enabled')->default(true);
            $table->unsignedSmallInteger('appointment_lead_minutes')->default(30);
            $table->boolean('task_immediate_enabled')->default(true);
            $table->unsignedSmallInteger('task_lead_minutes')->default(30);
        });
        foreach (DB::table('notification_settings')->get() as $setting) {
            DB::table('notification_settings')->where('id', $setting->id)
                ->update(['recipient_emails' => json_encode([$setting->recipient_email])]);
        }
        Schema::create('reminder_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('reminder_key', 64)->unique();
            $table->string('type', 30);
            $table->string('reminderable_type');
            $table->unsignedBigInteger('reminderable_id');
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at');
            $table->timestamps();
            $table->index(['reminderable_type', 'reminderable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_deliveries');
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn(['recipient_emails', 'appointment_immediate_enabled',
                'appointment_lead_minutes', 'task_immediate_enabled', 'task_lead_minutes']);
        });
    }
};
