<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    public const TYPES = ['follow_up', 'appointment', 'task'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'follow_up_enabled' => 'boolean',
            'appointment_enabled' => 'boolean',
            'task_enabled' => 'boolean',
            'recipient_emails' => 'array',
            'appointment_immediate_enabled' => 'boolean',
            'task_immediate_enabled' => 'boolean',
            'appointment_exact_enabled' => 'boolean',
            'task_exact_enabled' => 'boolean',
            'follow_up_email_enabled' => 'boolean',
            'follow_up_push_enabled' => 'boolean',
            'appointment_email_enabled' => 'boolean',
            'appointment_push_enabled' => 'boolean',
            'task_email_enabled' => 'boolean',
            'task_push_enabled' => 'boolean',
            'overdue_enabled' => 'boolean',
            'overdue_days' => 'integer',
            'task_notify_default' => 'boolean',
            'appointment_notify_default' => 'boolean',
            'appointment_lead_minutes' => 'integer',
            'task_lead_minutes' => 'integer',
            'follow_up_days' => 'integer',
            'appointment_days' => 'integer',
            'task_days' => 'integer',
            'follow_up_weekday' => 'integer',
            'appointment_weekday' => 'integer',
            'task_weekday' => 'integer',
            'follow_up_last_sent_at' => 'datetime',
            'appointment_last_sent_at' => 'datetime',
            'task_last_sent_at' => 'datetime',
            'last_run_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'recipient_email' => config('mail.from.address'),
            'timezone' => 'America/Lima',
        ])->refresh();
    }

    public function recipients(): array
    {
        $emails = array_values(array_unique(array_filter(
            $this->recipient_emails ?: [$this->recipient_email]
        )));

        return $emails ?: [config('mail.from.address')];
    }

    /** Canales activos para un tipo de aviso: correo, notificación de la app, o ambos. */
    public function channelsFor(string $type): array
    {
        return [
            'mail' => (bool) $this->getAttribute("{$type}_email_enabled"),
            'push' => (bool) $this->getAttribute("{$type}_push_enabled"),
        ];
    }

    public function wants(string $type): bool
    {
        return (bool) $this->getAttribute("{$type}_enabled")
            && in_array(true, $this->channelsFor($type), true);
    }

    public function leadMinutesFor(string $type): int
    {
        return max(1, (int) $this->getAttribute("{$type}_lead_minutes") ?: 30);
    }

    /** Hora local de la asesora: los avisos se leen en su horario, no en UTC. */
    public function now(): \Illuminate\Support\Carbon
    {
        return now($this->timezone);
    }
}
