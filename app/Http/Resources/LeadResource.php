<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'source' => $this->source,
            'status' => $this->status,
            'score' => $this->score,
            'budget' => $this->budget,
            'interest' => $this->interest,
            'notes' => $this->notes,
            'assigned_to' => $this->assigned_to,
            'assigned_to_name' => $this->whenLoaded('assignedTo', fn () => $this->assignedTo?->name),
            'party_type' => $this->party_type,
            'party_type_label' => $this->party_type_label,
            'notify_email' => (bool) $this->notify_email,
            'notify_push' => (bool) $this->notify_push,
            'contact_log_count' => $this->contactLogs()->count(),
            'follow_up_status' => $this->follow_up_status,
            'last_contact_at' => $this->last_contact_at?->toIso8601String(),
            'next_contact_at' => $this->next_contact_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
