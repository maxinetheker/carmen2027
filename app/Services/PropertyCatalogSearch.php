<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Http\Request;

class PropertyCatalogSearch
{
    public function search(Request $request): array
    {
        $filters = $this->filters($request);
        $available = Property::published()->where('status', 'available');
        $zones = (clone $available)->selectRaw('district, COUNT(*) as properties_count')
            ->groupBy('district')->orderByDesc('properties_count')->orderBy('district')->get();
        $priceBounds = (clone $available)
            ->when($filters['currency'], fn ($query, $currency) => $query->where('currency', $currency))
            ->selectRaw('MIN(price) as minimum, MAX(price) as maximum')->first();

        $query = Property::with('media')->published()->where('status', 'available');
        $query->when($filters['q'], function ($query, $term) {
            $query->where(function ($query) use ($term) {
                $query->where('title', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('district', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%");
            });
        });
        foreach (['district', 'operation', 'type', 'currency'] as $field) {
            $query->when($filters[$field], fn ($query, $value) => $query->where($field, $value));
        }
        $query->when($filters['min_price'] !== null,
            fn ($query) => $query->where('price', '>=', $filters['min_price']));
        $query->when($filters['max_price'] !== null,
            fn ($query) => $query->where('price', '<=', $filters['max_price']));

        $nearby = $filters['latitude'] !== null && $filters['longitude'] !== null;
        if ($nearby) {
            $latitudeDelta = $filters['radius'] / 111.32;
            $longitudeDelta = $filters['radius']
                / max(1, 111.32 * cos(deg2rad($filters['latitude'])));
            $query->whereBetween('latitude', [
                $filters['latitude'] - $latitudeDelta,
                $filters['latitude'] + $latitudeDelta,
            ])->whereBetween('longitude', [
                $filters['longitude'] - $longitudeDelta,
                $filters['longitude'] + $longitudeDelta,
            ]);
        }

        match ($filters['sort']) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'area_desc' => $query->orderByDesc('area'),
            'newest' => $query->latest(),
            default => $nearby
                ? $query->orderByRaw(
                    '((latitude - ?) * (latitude - ?) + (longitude - ?) * (longitude - ?))',
                    [$filters['latitude'], $filters['latitude'], $filters['longitude'], $filters['longitude']]
                ) : $query->ranked(),
        };

        return [
            'properties' => $query->paginate(9)->withQueryString(),
            'zones' => $zones, 'priceBounds' => $priceBounds, 'filters' => $filters,
        ];
    }

    private function filters(Request $request): array
    {
        $minimum = $this->number($request->input('min_price'));
        $maximum = $this->number($request->input('max_price'));
        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            [$minimum, $maximum] = [$maximum, $minimum];
        }
        $currency = in_array($request->input('currency'), ['USD', 'PEN'], true)
            ? $request->input('currency') : null;
        if (($minimum !== null || $maximum !== null
            || str_starts_with((string) $request->input('sort'), 'price_'))
            && ! $currency) $currency = 'USD';

        return [
            'q' => mb_substr(trim((string) $request->input('q')), 0, 100) ?: null,
            'district' => mb_substr(trim((string) $request->input('district')), 0, 100) ?: null,
            'operation' => in_array($request->input('operation'), ['venta', 'alquiler'], true) ? $request->input('operation') : null,
            'type' => array_key_exists((string) $request->input('type'), Property::TYPES) ? $request->input('type') : null,
            'currency' => $currency, 'min_price' => $minimum, 'max_price' => $maximum,
            'sort' => in_array($request->input('sort'), ['featured', 'price_asc', 'price_desc', 'area_desc', 'newest'], true) ? $request->input('sort') : 'featured',
            'latitude' => $this->coordinate($request->input('latitude'), -90, 90),
            'longitude' => $this->coordinate($request->input('longitude'), -180, 180),
            'radius' => in_array((int) $request->input('radius'), [3, 5, 10, 20, 40], true) ? (int) $request->input('radius') : 10,
        ];
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value >= 0 ? (float) $value : null;
    }

    private function coordinate(mixed $value, int $minimum, int $maximum): ?float
    {
        return is_numeric($value) && (float) $value >= $minimum && (float) $value <= $maximum
            ? (float) $value : null;
    }
}
