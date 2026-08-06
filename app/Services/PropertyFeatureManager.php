<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyFeature;

class PropertyFeatureManager
{
    public function replace(Property $property, array $features): void
    {
        $property->features()->delete();
        foreach ($features as $index => $feature) {
            $label = trim((string) ($feature['label'] ?? ''));
            $value = trim((string) ($feature['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }
            $icon = array_key_exists($feature['icon'] ?? '', PropertyFeature::ICONS)
                ? $feature['icon'] : 'info';
            $property->features()->create(compact('icon', 'label', 'value') + [
                'sort_order' => $index,
            ]);
        }
    }
}
