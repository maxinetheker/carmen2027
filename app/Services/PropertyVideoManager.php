<?php

namespace App\Services;

use App\Models\Property;

class PropertyVideoManager
{
    public function __construct(private YoutubeUrlParser $parser)
    {
    }

    public function replace(Property $property, array $videos): void
    {
        $property->youtubeVideos()->delete();
        $seen = [];
        foreach ($videos as $index => $video) {
            $url = trim((string) ($video['url'] ?? ''));
            $id = $this->parser->id($url);
            if (! $id || in_array($id, $seen, true)) continue;
            $seen[] = $id;
            $property->youtubeVideos()->create([
                'youtube_id' => $id,
                'original_url' => $url,
                'title' => trim((string) ($video['title'] ?? '')) ?: null,
                'sort_order' => $index,
            ]);
        }
    }
}
