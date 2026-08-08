<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Comprador / vendedor / ambos: sin esto no se puede saber de un vistazo a quién
        // hay que llamar para ofrecerle una propiedad y a quién para captarla.
        foreach (['leads', 'contacts'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('party_type', 20)->default('buyer')->after('phone');
                $blueprint->boolean('notify_email')->default(true)->after('next_contact_at');
                $blueprint->boolean('notify_push')->default(true)->after('notify_email');
                // Nombre con el que el número está guardado en la agenda del celular
                // (lo completa la sincronización de contactos de la app Android).
                $blueprint->string('device_contact_name')->nullable()->after('notify_push');
            });
        }
        // Los contactos también necesitan decir qué buscan o qué ofrecen: sin esto,
        // el resumen semanal de vendedores no puede nombrar la propiedad a captar.
        Schema::table('contacts', fn (Blueprint $table) => $table
            ->string('interest')->nullable()->after('party_type'));

        // Aviso por registro: cada tarea/cita decide si quiere recordatorio y con cuánta
        // anticipación, en vez de depender solo del interruptor global.
        Schema::table('task_items', function (Blueprint $table) {
            $table->boolean('notify_enabled')->default(true)->after('due_at');
            $table->unsignedSmallInteger('notify_lead_minutes')->nullable()->after('notify_enabled');
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('notify_enabled')->default(true)->after('status');
            $table->unsignedSmallInteger('notify_lead_minutes')->nullable()->after('notify_enabled');
        });

        Schema::table('notification_settings', function (Blueprint $table) {
            // Canales por tipo de aviso: correo, notificación de la app, o ambos.
            foreach (['follow_up', 'appointment', 'task'] as $prefix) {
                $table->boolean("{$prefix}_email_enabled")->default(true);
                $table->boolean("{$prefix}_push_enabled")->default(true);
            }
            // Segundo aviso exacto a la hora de inicio/vencimiento.
            $table->boolean('appointment_exact_enabled')->default(true);
            $table->boolean('task_exact_enabled')->default(true);
            // Cuántos días se sigue avisando de algo ya vencido antes de dejarlo pasar.
            $table->boolean('overdue_enabled')->default(true);
            $table->unsignedSmallInteger('overdue_days')->default(3);
            // Valores por defecto al crear tareas y citas nuevas.
            $table->boolean('task_notify_default')->default(true);
            $table->boolean('appointment_notify_default')->default(true);
        });

        // Bitácora de contacto: cada llamada, WhatsApp o correo con un prospecto o
        // contacto. Alimenta "último contacto" y la importación del registro de
        // llamadas del celular desde la app Android.
        Schema::create('contact_logs', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20)->default('call');
            $table->string('direction', 12)->default('outgoing');
            $table->string('outcome', 20)->nullable();
            $table->string('phone_number', 40)->nullable();
            $table->string('device_contact_name')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('contacted_at');
            $table->string('source', 20)->default('manual');
            // Clave estable de la llamada en el celular (número + timestamp): evita
            // duplicados cuando se vuelve a importar el mismo registro.
            $table->string('external_ref', 64)->nullable()->unique();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id', 'contacted_at']);
        });

        // Los registros existentes se crearon antes de que "avisar" fuera opcional:
        // se respeta el comportamiento anterior dejándolos activos.
        DB::table('task_items')->update(['notify_enabled' => true]);
        DB::table('appointments')->update(['notify_enabled' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_logs');
        Schema::table('contacts', fn (Blueprint $table) => $table->dropColumn('interest'));

        Schema::table('notification_settings', function (Blueprint $table) {
            $columns = ['appointment_exact_enabled', 'task_exact_enabled', 'overdue_enabled',
                'overdue_days', 'task_notify_default', 'appointment_notify_default'];
            foreach (['follow_up', 'appointment', 'task'] as $prefix) {
                $columns[] = "{$prefix}_email_enabled";
                $columns[] = "{$prefix}_push_enabled";
            }
            $table->dropColumn($columns);
        });

        Schema::table('appointments', fn (Blueprint $table) => $table
            ->dropColumn(['notify_enabled', 'notify_lead_minutes']));
        Schema::table('task_items', fn (Blueprint $table) => $table
            ->dropColumn(['notify_enabled', 'notify_lead_minutes']));

        foreach (['leads', 'contacts'] as $table) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint
                ->dropColumn(['party_type', 'notify_email', 'notify_push', 'device_contact_name']));
        }
    }
};
