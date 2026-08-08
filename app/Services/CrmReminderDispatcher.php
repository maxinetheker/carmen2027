<?php

namespace App\Services;

use App\Models\NotificationSetting;
use App\Services\Reminders\DigestPlanner;
use App\Services\Reminders\FollowUpReminderPlanner;
use App\Services\Reminders\ItemReminderPlanner;
use App\Services\Reminders\Reminder;
use App\Services\Reminders\ReminderSender;

/**
 * Orquesta los avisos del CRM. Corre cada minuto desde el scheduler y decide,
 * mirando la configuración y la hora local, qué corresponde enviar en ese momento:
 *
 * - avisos por registro (una tarea que vence, una cita que empieza, algo vencido);
 * - la llamada agendada a un cliente concreto;
 * - los resúmenes de la mañana (agenda del día, tareas pendientes, lista de
 *   clientes por contactar), que sí dependen de la hora configurada.
 */
class CrmReminderDispatcher
{
    public function __construct(
        private ItemReminderPlanner $items,
        private FollowUpReminderPlanner $followUps,
        private DigestPlanner $digests,
        private ReminderSender $sender,
    ) {
    }

    /**
     * @return array<int, string> Etiquetas de lo que efectivamente se envió.
     */
    public function run(bool $force = false, string $type = 'all'): array
    {
        $setting = NotificationSetting::current();
        $sent = [];
        // Se anota siempre, aunque no haya nada que enviar: sirve para comprobar
        // desde el panel que el cron del hosting está corriendo cada minuto.
        $setting->forceFill(['last_run_at' => now()])->saveQuietly();

        foreach (['appointment', 'task'] as $key) {
            if (! $this->handles($type, $key)) {
                continue;
            }
            foreach ($this->items->plan($setting, $key) as $reminder) {
                if ($this->sender->send($setting, $reminder)) {
                    $sent[] = $this->label($reminder);
                }
            }
        }

        if ($this->handles($type, 'follow_up')) {
            $listIsDue = $force || $this->digestIsDue($setting, 'follow_up');
            foreach ($this->followUps->plan($setting, $listIsDue) as $reminder) {
                if ($this->sender->send($setting, $reminder)) {
                    $sent[] = $this->label($reminder);
                }
            }
            if ($listIsDue) {
                $setting->update(['follow_up_last_sent_at' => now()]);
            }
        }

        foreach (['appointment', 'task'] as $key) {
            if (! $this->handles($type, $key)) {
                continue;
            }
            if (! $force && ! $this->digestIsDue($setting, $key)) {
                continue;
            }
            $reminder = $this->digests->plan($setting, $key);
            if ($reminder && $this->sender->send($setting, $reminder)) {
                $sent[] = "resumen de {$this->spanish($key)}";
            }
            $setting->update(["{$key}_last_sent_at" => now()]);
        }

        return $sent;
    }

    private function handles(string $requested, string $key): bool
    {
        return $requested === 'all' || $requested === $key;
    }

    /**
     * Los resúmenes salen una vez por día (o por semana) a partir de la hora
     * configurada; el resto de avisos no pasa por aquí y por eso ya no queda
     * todo represado hasta las 8 de la mañana.
     */
    private function digestIsDue(NotificationSetting $setting, string $key): bool
    {
        $now = $setting->now();
        if ($now->format('H:i') < (string) $setting->getAttribute("{$key}_time")) {
            return false;
        }

        $last = $setting->getAttribute("{$key}_last_sent_at")?->timezone($setting->timezone);
        if ($setting->getAttribute("{$key}_frequency") === 'daily') {
            return ! $last || ! $last->isSameDay($now);
        }

        return $now->dayOfWeekIso === (int) $setting->getAttribute("{$key}_weekday")
            && (! $last || ! $last->isSameWeek($now));
    }

    private function label(Reminder $reminder): string
    {
        $subject = $reminder->subject?->title
            ?? $reminder->subject?->full_name
            ?? 'lista';

        return $this->spanish($reminder->type).': '.$subject;
    }

    private function spanish(string $type): string
    {
        return ['follow_up' => 'clientes', 'appointment' => 'agenda', 'task' => 'tareas'][$type] ?? $type;
    }
}
