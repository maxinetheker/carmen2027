<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContactLog extends Model
{
    public const CHANNELS = [
        'call' => 'Llamada',
        'whatsapp' => 'WhatsApp',
        'sms' => 'Mensaje de texto',
        'email' => 'Correo',
        'meeting' => 'Reunión',
        'visit' => 'Visita',
        'other' => 'Otro',
    ];

    public const OUTCOMES = [
        'answered' => 'Contestó',
        'no_answer' => 'No contestó',
        'voicemail' => 'Buzón de voz',
        'busy' => 'Ocupado',
        'scheduled' => 'Se agendó una cita',
        'follow_up' => 'Quedó en volver a hablar',
        'not_interested' => 'No le interesa',
        'other' => 'Otro',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getChannelLabelAttribute(): string
    {
        return self::CHANNELS[$this->channel] ?? ucfirst((string) $this->channel);
    }

    public function getOutcomeLabelAttribute(): ?string
    {
        return $this->outcome ? (self::OUTCOMES[$this->outcome] ?? ucfirst($this->outcome)) : null;
    }

    public function getDurationLabelAttribute(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }
        $minutes = intdiv($this->duration_seconds, 60);
        $seconds = $this->duration_seconds % 60;

        return $minutes ? "{$minutes} min {$seconds} s" : "{$seconds} s";
    }

    /**
     * Registrar un contacto es lo que mueve la fecha de "último contacto" del
     * prospecto o contacto; hacerlo aquí evita que la app, el panel y la
     * importación de llamadas tengan que acordarse cada una por su cuenta.
     */
    protected static function booted(): void
    {
        static::created(function (self $log) {
            $subject = $log->subject;
            if (! $subject) {
                return;
            }
            if (! $subject->last_contact_at || $subject->last_contact_at->lt($log->contacted_at)) {
                $subject->forceFill(['last_contact_at' => $log->contacted_at])->save();
            }
        });
    }
}
