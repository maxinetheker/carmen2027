<?php

namespace App\Jobs;

use App\Jobs\Concerns\SendsReminderMail;
use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Notifications\CrmReminderDigest;
use Illuminate\Bus\Queueable;

class SendAppointmentRemindersJob
{
    use Queueable, SendsReminderMail;

    public function __construct(public int $settingId)
    {
    }

    public function handle(): bool
    {
        $setting = NotificationSetting::findOrFail($this->settingId);
        $appointments = Appointment::query()
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereBetween('starts_at', [now(), now()->addDays($setting->appointment_days)->endOfDay()])
            ->orderBy('starts_at')->limit(100)->get();
        if ($appointments->isEmpty()) return false;

        $items = $appointments->map(fn (Appointment $appointment) => [
            'title' => $appointment->title,
            'meta' => $appointment->starts_at->format('d/m/Y H:i')
                .' · '.($appointment->location ?: 'Lugar por confirmar'),
            'url' => route('admin.appointments.edit', $appointment),
        ])->all();
        $this->sendToRecipients($setting, new CrmReminderDigest('Próximas reuniones y visitas',
            "Agenda de los próximos {$setting->appointment_days} días.", $items));

        return true;
    }
}
