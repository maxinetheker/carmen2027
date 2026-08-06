<?php

use App\Models\Property;
use App\Support\RichTextSanitizer;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('crm:send-reminders {--force} {--type=all}', function () {
    $sent = app(\App\Services\CrmReminderDispatcher::class)->run(
        (bool) $this->option('force'), (string) $this->option('type')
    );
    $this->info($sent ? 'Avisos enviados: '.implode(', ', $sent) : 'No hay avisos por enviar.');
})->purpose('Envía los recordatorios configurados del CRM');

Schedule::command('crm:send-reminders')
    ->everyMinute()
    ->withoutOverlapping(10);

// En hosting compartido no se puede dejar un proceso `queue:listen` corriendo para
// siempre (lo que sí hace `composer dev` en local): un worker que procesa lo pendiente
// y se detiene cada minuto es el patrón estándar para que la cola avance igual (usada
// por ejemplo por la generación de presentaciones PDF en GeneratePropertyPresentationJob).
Schedule::command('queue:work --stop-when-empty --max-time=55')
    ->everyMinute()
    ->withoutOverlapping(5);

Artisan::command('crm:normalize-property-text', function () {
    $sanitizer = app(RichTextSanitizer::class);
    $updated = 0;
    Property::whereNotNull('description')->each(function (Property $property) use ($sanitizer, &$updated) {
        $clean = $sanitizer->clean($property->description);
        if ($clean !== $property->description) {
            $property->update(['description' => $clean]);
            $updated++;
        }
    });
    $this->info("Descripciones normalizadas: {$updated}.");
})->purpose('Normaliza texto decorativo (negrita/cursiva de redes) en descripciones ya guardadas');
