<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationSettingRules;
use App\Models\DeviceToken;
use App\Models\NotificationSetting;
use App\Services\CrmReminderDispatcher;
use App\Services\Reminders\Reminder;
use App\Services\FcmSender;
use App\Services\Reminders\ReminderSender;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function edit()
    {
        return view('admin.notifications.edit', [
            'setting' => NotificationSetting::current(),
            'weekdays' => [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'],
            'timezones' => NotificationSettingRules::TIMEZONES,
            'devices' => DeviceToken::count(),
            'lastRun' => NotificationSetting::current()->last_run_at,
        ]);
    }

    public function update(Request $request)
    {
        $data = NotificationSettingRules::withBooleans(
            $request->validate($this->rules()), $request
        );
        $emails = $this->parseEmails($data['recipient_emails']);
        $data['recipient_emails'] = $emails;
        $data['recipient_email'] = $emails[0];
        NotificationSetting::current()->update($data);

        return back()->with('success', 'Configuración de notificaciones actualizada.');
    }

    public function run(CrmReminderDispatcher $dispatcher)
    {
        $sent = $dispatcher->run(true);

        return back()->with('success', $sent
            ? 'Se enviaron '.count($sent).' aviso(s): '.implode(' · ', array_slice($sent, 0, 6))
            : 'Revisado: en este momento no hay nada que avisar.');
    }

    public function sendTest(ReminderSender $sender)
    {
        $setting = NotificationSetting::current();
        $sender->send($setting, new Reminder(
            type: 'task',
            heading: 'Notificación de prueba',
            intro: 'Si estás leyendo esto, los avisos del CRM llegan correctamente a este buzón.',
            items: [[
                'title' => 'Prueba de entrega',
                'meta' => 'Enviada el '.$setting->now()->format('d/m/Y H:i'),
                'detail' => 'Así se verá el detalle de cada tarea o cita.',
                'url' => route('admin.dashboard'),
                'action' => 'Abrir el panel',
            ]],
            pushTitle: '🔔 Prueba de notificación',
            pushBody: 'Los avisos del CRM llegan bien a este celular.',
            pushData: ['route' => 'dashboard'],
        ));

        return back()->with('success', $this->testSummary($setting));
    }

    private function testSummary(NotificationSetting $setting): string
    {
        $devices = DeviceToken::count();
        $channels = $setting->channelsFor('task');
        $message = $channels['mail']
            ? 'Correo de prueba enviado a '.count($setting->recipients()).' destinatario(s).'
            : 'Correo no enviado: el canal de correo está desactivado para tareas.';

        if (! $channels['push']) {
            return $message.' Push no enviado: el canal de notificaciones está desactivado.';
        }
        if (! app(FcmSender::class)->isConfigured()) {
            return $message.' Push no enviado: faltan las credenciales de Firebase en el servidor.';
        }

        return $message.($devices
            ? " Push enviado a {$devices} dispositivo(s)."
            : ' Push no enviado: todavía no hay celulares con la app registrada.');
    }

    private function rules(): array
    {
        return NotificationSettingRules::shared() + [
            'recipient_emails' => ['required', 'string', 'max:2000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $emails = $this->parseEmails((string) $value);
                    if (! $emails) $fail('Agrega al menos un correo destinatario.');
                    if (count($emails) > 10) $fail('Puedes agregar como máximo 10 correos.');
                    foreach ($emails as $email) {
                        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("El correo {$email} no es válido.");
                        }
                    }
                }],
        ];
    }

    private function parseEmails(string $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($email) => strtolower(trim($email)),
            preg_split('/[\s,;]+/', $value) ?: []
        ))));
    }
}
