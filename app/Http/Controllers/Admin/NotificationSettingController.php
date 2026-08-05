<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\NotificationSetting;
use App\Notifications\CrmReminderDigest;
use App\Services\CrmReminderDispatcher;
use App\Services\FcmSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class NotificationSettingController extends Controller
{
    public function edit()
    {
        return view('admin.notifications.edit', [
            'setting' => NotificationSetting::current(),
            'weekdays' => [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'],
            'timezones' => ['America/Lima' => 'Lima', 'America/Bogota' => 'Bogotá',
                'America/Mexico_City' => 'Ciudad de México', 'America/New_York' => 'Nueva York'],
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate($this->rules());
        foreach (['follow_up', 'appointment', 'task'] as $key) {
            $data["{$key}_enabled"] = $request->boolean("{$key}_enabled");
        }
        foreach (['appointment', 'task'] as $key) {
            $data["{$key}_immediate_enabled"] = $request->boolean("{$key}_immediate_enabled");
        }
        $emails = $this->parseEmails($data['recipient_emails']);
        $data['recipient_emails'] = $emails;
        $data['recipient_email'] = $emails[0];
        NotificationSetting::current()->update($data);

        return back()->with('success', 'Configuración de notificaciones actualizada.');
    }

    public function run(CrmReminderDispatcher $dispatcher)
    {
        $sent = $dispatcher->run(true);
        $message = $sent
            ? 'Se procesaron los avisos activos. Revisa el correo configurado.'
            : 'No hay registros que requieran un aviso en este momento.';

        return back()->with('success', $message);
    }

    public function sendTest(FcmSender $fcm)
    {
        $setting = NotificationSetting::current();
        $recipients = $setting->recipients();

        $notification = new CrmReminderDigest(
            'Notificación de prueba',
            'Este es un mensaje de prueba enviado desde el panel de notificaciones del CRM.',
            [[
                'title' => 'Prueba de entrega',
                'meta' => 'Enviada el '.now($setting->timezone)->format('d/m/Y H:i'),
                'url' => route('admin.dashboard'),
            ]],
        );
        foreach ($recipients as $email) {
            Notification::route('mail', $email)->notify(clone $notification);
        }

        $tokens = DeviceToken::query()->pluck('token')->all();
        $message = 'Correo de prueba enviado a '.count($recipients).' destinatario(s).';

        if (! $fcm->isConfigured()) {
            $message .= ' Push no enviado: faltan las credenciales de Firebase en el servidor.';
        } elseif (! $tokens) {
            $message .= ' Push no enviado: todavía no hay dispositivos Android registrados.';
        } else {
            $fcm->send($tokens, 'Notificación de prueba', 'Este es un mensaje de prueba enviado desde el panel del CRM.');
            $message .= ' Push enviado a '.count($tokens).' dispositivo(s) registrado(s).';
        }

        return back()->with('success', $message);
    }

    private function rules(): array
    {
        $rules = [
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
            'timezone' => ['required', Rule::in(['America/Lima', 'America/Bogota',
                'America/Mexico_City', 'America/New_York'])],
        ];
        foreach (['follow_up', 'appointment', 'task'] as $key) {
            $rules["{$key}_enabled"] = ['nullable', 'boolean'];
            $rules["{$key}_frequency"] = ['required', Rule::in(['daily', 'weekly'])];
            $rules["{$key}_time"] = ['required', 'date_format:H:i'];
            $rules["{$key}_weekday"] = ['required', 'integer', 'between:1,7'];
            $rules["{$key}_days"] = ['required', 'integer', 'between:0,365'];
        }
        foreach (['appointment', 'task'] as $key) {
            $rules["{$key}_immediate_enabled"] = ['nullable', 'boolean'];
            $rules["{$key}_lead_minutes"] = ['required', 'integer', 'between:5,10080'];
        }

        return $rules;
    }

    private function parseEmails(string $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($email) => strtolower(trim($email)),
            preg_split('/[\s,;]+/', $value) ?: []
        ))));
    }
}
