<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'channel_label' => $this->channel_label,
            'direction' => $this->direction,
            'outcome' => $this->outcome,
            'outcome_label' => $this->outcome_label,
            'phone_number' => $this->phone_number,
            'device_contact_name' => $this->device_contact_name,
            'duration_seconds' => $this->duration_seconds,
            'duration_label' => $this->duration_label,
            'notes' => $this->notes,
            'source' => $this->source,
            'contacted_at' => $this->contacted_at?->toIso8601String(),
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
        ];
    }
}
