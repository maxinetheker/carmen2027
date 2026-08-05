<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
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
            'document' => $this->document,
            'company' => $this->company,
            'address' => $this->address,
            'birthday' => $this->birthday?->toDateString(),
            'notes' => $this->notes,
            'follow_up_status' => $this->follow_up_status,
            'last_contact_at' => $this->last_contact_at?->toIso8601String(),
            'next_contact_at' => $this->next_contact_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
