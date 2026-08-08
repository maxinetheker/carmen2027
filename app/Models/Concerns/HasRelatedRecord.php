<?php

namespace App\Models\Concerns;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;

/**
 * `related_type` guarda una etiqueta corta ("lead", "contact"…) en vez de la clase
 * completa, así que la relación polimórfica de Eloquent no aplica: este trait
 * traduce esa etiqueta al modelo real para poder nombrarlo en avisos y listados.
 */
trait HasRelatedRecord
{
    public const RELATED_MODELS = [
        'lead' => Lead::class,
        'contact' => Contact::class,
        'property' => Property::class,
        'deal' => Deal::class,
    ];

    public const RELATED_LABELS = [
        'lead' => 'Prospecto', 'contact' => 'Contacto',
        'property' => 'Propiedad', 'deal' => 'Oportunidad',
    ];

    public function relatedRecord()
    {
        $model = self::RELATED_MODELS[$this->related_type] ?? null;

        return $model && $this->related_id ? $model::find($this->related_id) : null;
    }

    public function getRelatedLabelAttribute(): ?string
    {
        $record = $this->relatedRecord();
        if (! $record) {
            return null;
        }

        return $record->full_name ?? $record->title ?? null;
    }

    public function getRelatedTypeLabelAttribute(): ?string
    {
        return self::RELATED_LABELS[$this->related_type] ?? null;
    }
}
