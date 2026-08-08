<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    public const TYPES = [
        'visit' => 'Visita a la propiedad', 'call' => 'Llamada', 'meeting' => 'Reunión',
        'signing' => 'Firma', 'capture' => 'Cita de captación', 'other' => 'Otro',
    ];

    public const STATUSES = [
        'scheduled' => 'Programada', 'confirmed' => 'Confirmada',
        'done' => 'Realizada', 'cancelled' => 'Cancelada',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'notify_enabled' => 'boolean',
            'notify_lead_minutes' => 'integer',
        ];
    }

    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function lead() { return $this->belongsTo(Lead::class); }
    public function contact() { return $this->belongsTo(Contact::class); }
    public function property() { return $this->belongsTo(Property::class); }

    public function isOpen(): bool
    {
        return in_array($this->status, ['scheduled', 'confirmed'], true);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    /** Con quién es la cita, para que el aviso diga algo más que el título. */
    public function getPersonNameAttribute(): ?string
    {
        return $this->contact?->full_name ?? $this->lead?->full_name;
    }

    public function reminderAt(): ?\Illuminate\Support\Carbon
    {
        return $this->starts_at;
    }
}
