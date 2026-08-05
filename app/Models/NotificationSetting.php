<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
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
}
