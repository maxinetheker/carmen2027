<?php

namespace App\Services\Reminders;

use App\Models\NotificationSetting;
use App\Support\HumanDate;
use Illuminate\Database\Eloquent\Model;

/**
 * El texto de los avisos de tareas y citas.
 *
 * Una notificación que solo dice "Tareas pendientes" obliga a abrir la app para
 * saber de qué se trata, así que aquí se arma un mensaje que se entiende solo:
 * qué es, con quién, para cuándo y si ya venció.
 */
class ItemReminderCopy
{
    public function build(
        NotificationSetting $setting,
        string $type,
        Model $record,
        string $stage,
        string $urgency,
        int $lead,
    ): Reminder {
        $at = $record->reminderAt()->copy();
        $when = HumanDate::short($at, $setting->timezone);
        $distance = HumanDate::distance($at, $setting->timezone);
        $isTask = $type === 'task';
        $noun = $isTask ? 'Tarea' : 'Cita';
        $context = $this->context($record, $isTask);
        $url = route($isTask ? 'admin.tasks.edit' : 'admin.appointments.edit', $record);

        return Reminder::forRecord(
            type: $type,
            subject: $record,
            dedupeKey: sha1("{$type}|{$record->getKey()}|{$at->timestamp}|{$stage}"),
            heading: $this->heading($isTask, $urgency, $distance),
            intro: match ($urgency) {
                'overdue' => "{$noun}: «{$record->title}». La fecha era {$when} ({$distance}).",
                'now' => "{$noun}: «{$record->title}», programada para {$when}.",
                default => "{$noun}: «{$record->title}». Este es el aviso de {$lead} minutos antes; es {$when}.",
            },
            item: [
                'title' => $record->title,
                'meta' => $this->meta($record, $isTask, $when, $distance, $urgency),
                'detail' => $context,
                'url' => $url,
                'action' => $isTask ? 'Abrir la tarea' : 'Abrir la cita',
            ],
            pushTitle: $this->pushTitle($isTask, $urgency, $distance),
            pushBody: trim($record->title.($context ? " · {$context}" : '')." · {$when}"),
            pushData: [
                // `route` es lo que permite que tocar la notificación abra la ficha
                // exacta en la app en lugar del panel general.
                'route' => ($isTask ? 'tasks/' : 'appointments/').$record->getKey(),
                'record_id' => (string) $record->getKey(),
                'url' => $url,
            ],
            scheduledFor: $at,
            urgency: $urgency,
        );
    }

    private function heading(bool $isTask, string $urgency, string $distance): string
    {
        return match ($urgency) {
            'overdue' => $isTask ? 'Tarea vencida' : 'Cita pendiente de cerrar',
            'now' => $isTask ? 'Una tarea vence ahora' : 'Una cita empieza ahora',
            default => $isTask ? "Una tarea vence {$distance}" : "Una cita empieza {$distance}",
        };
    }

    private function pushTitle(bool $isTask, string $urgency, string $distance): string
    {
        return match ($urgency) {
            'overdue' => $isTask ? "⚠️ Tarea vencida ({$distance})" : "⚠️ Cita sin cerrar ({$distance})",
            'now' => $isTask ? '⏰ Una tarea vence ahora' : '⏰ Una cita empieza ahora',
            default => $isTask ? "⏳ Tarea: vence {$distance}" : "⏳ Cita: empieza {$distance}",
        };
    }

    private function meta(Model $record, bool $isTask, string $when, string $distance, string $urgency): string
    {
        if ($isTask) {
            return ($urgency === 'overdue' ? 'Venció ' : 'Vence ')
                ."{$when} ({$distance}) · Prioridad {$record->priority_label}"
                .' · Estado '.mb_strtolower($record->status_label);
        }

        $parts = [($urgency === 'overdue' ? 'Era ' : 'Empieza ')."{$when} ({$distance})"];
        if ($record->ends_at) {
            $parts[] = 'termina '.$record->ends_at->format('H:i');
        }
        $parts[] = $record->type_label;
        $parts[] = $record->location ?: 'lugar por confirmar';

        return implode(' · ', $parts);
    }

    /** Con quién o sobre qué es; sin esto el aviso obliga a abrir la app. */
    private function context(Model $record, bool $isTask): ?string
    {
        if ($isTask) {
            $label = $record->related_label;

            return $label ? $record->related_type_label.': '.$label : null;
        }

        $parts = array_filter([$record->person_name, $record->property?->title]);

        return $parts ? implode(' · ', $parts) : null;
    }
}
