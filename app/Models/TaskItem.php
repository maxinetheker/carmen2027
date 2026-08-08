<?php

namespace App\Models;

use App\Models\Concerns\HasRelatedRecord;
use Illuminate\Database\Eloquent\Model;

class TaskItem extends Model
{
    use HasRelatedRecord;

    public const PRIORITIES = [
        'low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'urgent' => 'Urgente',
    ];

    public const STATUSES = [
        'pending' => 'Pendiente', 'doing' => 'En curso', 'done' => 'Completada',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'notify_enabled' => 'boolean',
            'notify_lead_minutes' => 'integer',
        ];
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isOpen(): bool
    {
        return $this->status !== 'done';
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst((string) $this->priority);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    /** El momento que dispara los recordatorios; sin fecha límite no hay avisos. */
    public function reminderAt(): ?\Illuminate\Support\Carbon
    {
        return $this->due_at;
    }
}
