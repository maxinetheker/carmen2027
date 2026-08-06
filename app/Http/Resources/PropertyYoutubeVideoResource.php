<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyYoutubeVideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'youtube_id' => $this->youtube_id,
            'original_url' => $this->original_url,
            'title' => $this->title,
            'embed_url' => $this->embed_url,
            'thumbnail_url' => $this->thumbnail_url,
            'sort_order' => $this->sort_order,
        ];
    }
}
