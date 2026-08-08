<?php

namespace App\Models\Concerns;

use App\Models\ContactLog;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Todo lo que comparten prospectos y contactos: en qué lado de la operación están
 * (compra, venta o ambas), cómo quieren recibir los avisos y la bitácora de
 * llamadas y mensajes que sostiene el seguimiento.
 */
trait TracksFollowUp
{
    public const PARTY_TYPES = [
        'buyer' => 'Comprador',
        'seller' => 'Vendedor / propietario',
        'both' => 'Comprador y vendedor',
        'other' => 'Otro (referidor, colega…)',
    ];

    public const FOLLOW_UP_STATUSES = [
        'active' => 'Activo',
        'paused' => 'Pausado',
        'do_not_contact' => 'No contactar',
    ];

    public function contactLogs(): MorphMany
    {
        return $this->morphMany(ContactLog::class, 'subject')
            ->orderByDesc('contacted_at');
    }

    public function getPartyTypeLabelAttribute(): string
    {
        return self::PARTY_TYPES[$this->party_type] ?? 'Comprador';
    }

    public function getPhoneKeyAttribute(): ?string
    {
        return PhoneNumber::key($this->phone);
    }

    /** Un aviso solo tiene sentido si queda algún canal por el que enviarlo. */
    public function wantsReminders(): bool
    {
        return $this->follow_up_status === 'active'
            && ($this->notify_email || $this->notify_push);
    }

    /**
     * Personas que ya tocan avisar: las que tienen fecha de próximo contacto
     * cumplida y las que llevan `$days` días sin actividad registrada.
     */
    public function scopeDueForFollowUp(Builder $query, int $days): Builder
    {
        $cutoff = now()->subDays($days);

        return $query->where('follow_up_status', 'active')
            ->where(fn ($due) => $due
                ->where('next_contact_at', '<=', now())
                ->orWhere(fn ($automatic) => $automatic
                    ->whereNull('next_contact_at')
                    ->where(fn ($inactive) => $inactive
                        ->where('last_contact_at', '<=', $cutoff)
                        ->orWhere(fn ($never) => $never
                            ->whereNull('last_contact_at')
                            ->where('created_at', '<=', $cutoff)))));
    }
}
