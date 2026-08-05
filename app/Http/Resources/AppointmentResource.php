<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'status' => $this->status,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'location' => $this->location,
            'assigned_to' => $this->assigned_to,
            'assigned_to_name' => $this->whenLoaded('assignedTo', fn () => $this->assignedTo?->name),
            'lead_id' => $this->lead_id,
            'lead_name' => $this->whenLoaded('lead', fn () => $this->lead?->full_name),
            'contact_id' => $this->contact_id,
            'contact_name' => $this->whenLoaded('contact', fn () => $this->contact?->full_name),
            'property_id' => $this->property_id,
            'property_title' => $this->whenLoaded('property', fn () => $this->property?->title),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
