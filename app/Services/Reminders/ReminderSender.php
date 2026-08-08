<?php

namespace App\Services\Reminders;

use App\Models\DeviceToken;
use App\Models\NotificationSetting;
use App\Models\ReminderDelivery;
use App\Notifications\CrmReminderDigest;
use App\Services\FcmSender;
use Illuminate\Support\Facades\Notification;

class ReminderSender
{
    public function __construct(private FcmSender $fcm)
    {
    }

    /**
     * Entrega un aviso por los canales activos para su tipo y lo anota para no
     * repetirlo. Devuelve false cuando el aviso ya se había enviado antes, así
     * el planificador puede seguir sin ensuciar el resumen de la ejecución.
     */
    public function send(NotificationSetting $setting, Reminder $reminder): bool
    {
        if ($reminder->dedupeKey && $this->alreadySent($reminder->dedupeKey)) {
            return false;
        }

        $channels = $setting->channelsFor($reminder->type);
        if (! $channels['mail'] && ! $channels['push']) {
            return false;
        }

        if ($channels['mail']) {
            $notification = new CrmReminderDigest(
                $reminder->heading, $reminder->intro, $reminder->items, $reminder->urgency
            );
            foreach ($setting->recipients() as $email) {
                Notification::route('mail', $email)->notify(clone $notification);
            }
        }

        if ($channels['push']) {
            $this->push($reminder);
        }

        if ($reminder->dedupeKey) {
            $this->remember($reminder);
        }

        return true;
    }

    private function push(Reminder $reminder): void
    {
        $tokens = DeviceToken::query()->pluck('token')->all();
        if ($tokens === []) {
            return;
        }

        // `route` es lo que hace que tocar la notificación abra la ficha exacta en
        // la app en vez de dejar al usuario en el panel general buscándola.
        $this->fcm->send($tokens, $reminder->pushTitle, $reminder->pushBody, array_filter([
            'type' => $reminder->type,
            'urgency' => $reminder->urgency,
        ] + $reminder->pushData));
    }

    private function alreadySent(string $key): bool
    {
        return ReminderDelivery::where('reminder_key', $key)->exists();
    }

    private function remember(Reminder $reminder): void
    {
        ReminderDelivery::firstOrCreate(['reminder_key' => $reminder->dedupeKey], [
            'type' => $reminder->type,
            'reminderable_type' => $reminder->subject ? $reminder->subject::class : 'digest',
            'reminderable_id' => $reminder->subject?->getKey() ?? 0,
            'scheduled_for' => $reminder->scheduledFor ?? now(),
            'sent_at' => now(),
        ]);
    }
}
