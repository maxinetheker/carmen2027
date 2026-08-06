<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Collection;

class PropertyMediaReorderer
{
    public function apply(Property $property, array $order, ?int $coverMediaId): Collection
    {
        $ids = $property->media()->pluck('id')->all();
        foreach ($order as $index => $mediaId) {
            if (! in_array($mediaId, $ids, true)) {
                continue;
            }
            $property->media()->whereKey($mediaId)->update(['sort_order' => $index]);
        }

        if ($coverMediaId && in_array($coverMediaId, $ids, true)
            && $property->media()->whereKey($coverMediaId)->where('type', 'image')->exists()) {
            $property->media()->update(['is_cover' => false]);
            $property->media()->whereKey($coverMediaId)->update(['is_cover' => true]);
        }

        return $property->media()->orderBy('sort_order')->get();
    }
}
