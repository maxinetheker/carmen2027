<?php

namespace App\Support;

/**
 * El bloque «avisarme» que comparten tareas y citas. La anticipación se deja vacía
 * para heredar la de Notificaciones, así cambiar la preferencia general no obliga
 * a editar registro por registro.
 */
class ReminderFields
{
    public const LEAD_CHOICES = [
        '' => 'Usar la anticipación general',
        '5' => '5 minutos antes', '10' => '10 minutos antes', '15' => '15 minutos antes',
        '30' => '30 minutos antes', '60' => '1 hora antes', '120' => '2 horas antes',
        '360' => '6 horas antes', '1440' => '1 día antes',
    ];

    /** @return array<int, array<string, mixed>> */
    public static function forTask(): array
    {
        return self::block(
            'Avisarme de esta tarea',
            'Recibirás un aviso antes de que venza, otro justo al vencer y, si queda sin cerrar, un recordatorio diario.',
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function forAppointment(): array
    {
        return self::block(
            'Avisarme de esta cita',
            'Recibirás un aviso antes de que empiece y otro a la hora exacta de inicio.',
        );
    }

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'notify_enabled' => ['nullable', 'boolean'],
            'notify_lead_minutes' => ['nullable', 'integer', 'between:0,10080'],
        ];
    }

    /** 0 llega desde la app y significa "sin anticipación propia". */
    public static function normalize(array $data): array
    {
        if (($data['notify_lead_minutes'] ?? null) !== null && (int) $data['notify_lead_minutes'] < 5) {
            $data['notify_lead_minutes'] = null;
        }

        return $data;
    }

    /** @return array<int, array<string, mixed>> */
    private static function block(string $label, string $help): array
    {
        return [
            ['name' => 'notify_enabled', 'label' => $label, 'type' => 'checkbox', 'help' => $help],
            ['name' => 'notify_lead_minutes', 'label' => 'Avisarme con', 'type' => 'select',
                'options' => self::LEAD_CHOICES,
                'help' => 'Solo aplica al primer aviso; el de la hora exacta siempre llega igual.'],
        ];
    }
}
