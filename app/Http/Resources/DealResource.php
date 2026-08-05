<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'stage' => $this->stage,
            'value' => $this->value,
            'currency' => $this->currency,
            'probability' => $this->probability,
            'expected_close' => $this->expected_close?->toDateString(),
            'notes' => $this->notes,
            'lead_id' => $this->lead_id,
            'lead_name' => $this->whenLoaded('lead', fn () => $this->lead?->full_name),
            'contact_id' => $this->contact_id,
            'contact_name' => $this->whenLoaded('contact', fn () => $this->contact?->full_name),
            'property_id' => $this->property_id,
            'property_title' => $this->whenLoaded('property', fn () => $this->property?->title),
            'owner_id' => $this->owner_id,
            'owner_name' => $this->whenLoaded('owner', fn () => $this->owner?->name),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
