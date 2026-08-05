<?php

namespace App\Jobs;

use App\Jobs\Concerns\SendsReminderMail;
use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\ReminderDelivery;
use App\Notifications\CrmReminderDigest;
use Illuminate\Bus\Queueable;

class SendImmediateAppointmentReminderJob
{
    use Queueable, SendsReminderMail;

    public function __construct(public int $settingId)
    {
    }

    public function handle(): bool
    {
        $setting = NotificationSetting::findOrFail($this->settingId);
        $now = now();
        $appointments = Appointment::query()
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereBetween('starts_at', [$now, $now->copy()->addMinutes($setting->appointment_lead_minutes)])
            ->orderBy('starts_at')->limit(50)->get()
            ->reject(fn ($item) => ReminderDelivery::where(
                'reminder_key', $this->key($item)
            )->exists())->values();
        if ($appointments->isEmpty()) return false;

        $items = $appointments->map(fn (Appointment $item) => [
            'title' => $item->title,
            'meta' => 'Comienza: '.$item->starts_at->format('d/m/Y H:i')
                .' · '.($item->location ?: 'Lugar por confirmar'),
            'url' => route('admin.appointments.edit', $item),
        ])->all();
        $this->sendToRecipients($setting, new CrmReminderDigest(
            'Recordatorio inmediato de agenda',
            "Estas actividades comienzan en los próximos {$setting->appointment_lead_minutes} minutos.", $items
        ));
        foreach ($appointments as $item) $this->remember($item);

        return true;
    }

    private function key(Appointment $item): string
    {
        return sha1("appointment|{$item->id}|{$item->starts_at->timestamp}");
    }

    private function remember(Appointment $item): void
    {
        ReminderDelivery::firstOrCreate(['reminder_key' => $this->key($item)], [
            'type' => 'appointment', 'reminderable_type' => Appointment::class,
            'reminderable_id' => $item->id, 'scheduled_for' => $item->starts_at,
            'sent_at' => now(),
        ]);
    }
}
