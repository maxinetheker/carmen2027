<?php

namespace App\Http\Resources;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'assigned_to' => $this->assigned_to,
            'assigned_to_name' => $this->whenLoaded('assignedTo', fn () => $this->assignedTo?->name),
            'priority' => $this->priority,
            'status' => $this->status,
            'due_at' => $this->due_at?->toIso8601String(),
            'related_type' => $this->related_type,
            'related_id' => $this->related_id,
            'related_label' => $this->relatedLabel(),
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function relatedLabel(): ?string
    {
        if (! $this->related_type || ! $this->related_id) {
            return null;
        }

        return match ($this->related_type) {
            'lead' => Lead::find($this->related_id)?->full_name,
            'contact' => Contact::find($this->related_id)?->full_name,
            'property' => Property::find($this->related_id)?->title,
            'deal' => Deal::find($this->related_id)?->title,
            default => null,
        };
    }
}
