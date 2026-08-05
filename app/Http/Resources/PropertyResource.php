<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'code' => $this->code,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'operation' => $this->operation,
            'operation_label' => $this->operation_label,
            'district' => $this->district,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'price' => $this->price,
            'currency' => $this->currency,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'area' => $this->area,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'featured' => $this->featured,
            'is_published' => $this->is_published,
            'show_in_hero' => $this->show_in_hero,
            'priority' => $this->priority,
            'description' => $this->description,
            'cover_url' => $this->cover_url,
            'media' => PropertyMediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
