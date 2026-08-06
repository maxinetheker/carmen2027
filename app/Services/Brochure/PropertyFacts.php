<?php

namespace App\Services\Brochure;

use App\Models\Property;
use App\Models\SiteSetting;

/**
 * Small text/data derivations shared by the renderer and its preview, kept out of
 * PresentationRenderer to stay under this project's 150-line-per-file convention.
 */
class PropertyFacts
{
    public function titleTiers(int $base): array
    {
        return [
            ['max' => 35, 'size' => $base.'pt'],
            ['max' => 55, 'size' => ($base - 4).'pt'],
            ['max' => 80, 'size' => ($base - 8).'pt'],
            ['max' => 999, 'size' => max(14, $base - 12).'pt'],
        ];
    }

    public function subtitle(Property $property): string
    {
        return e(collect([$property->district, $property->address])->filter()->implode(' · '));
    }

    public function priceMain(Property $property): string
    {
        return $property->currency.' '.number_format((float) $property->price, 0);
    }

    public function priceSub(Property $property): string
    {
        if ($property->type === 'terreno' && (float) $property->area > 0) {
            $perM2 = (float) $property->price / (float) $property->area;

            return $property->currency.' '.number_format($perM2, 0).' por m²';
        }

        return "{$property->area} m² · {$property->bedrooms} dorm · {$property->bathrooms_label} baños";
    }

    public function steps(): array
    {
        return [
            ['t' => 'Agende su visita', 'd' => 'Conozca la propiedad en persona con nuestro acompañamiento.'],
            ['t' => 'Reciba la ficha completa', 'd' => 'Documentación y detalle de la propiedad en sus manos.'],
            ['t' => 'Cierre con seguridad', 'd' => 'Acompañamiento en todo el proceso hasta la firma.'],
        ];
    }

    public function agent(): array
    {
        $settings = SiteSetting::values();

        return [
            'name' => config('app.name', 'Carmen Mestanza'),
            'role' => $settings['ceo_title'] ?? 'Asesora Inmobiliaria',
            'address' => $settings['service_area'] ?? null,
            'phone' => $settings['phone'] ?? $settings['whatsapp'] ?? null,
            'email' => $settings['email'] ?? null,
        ];
    }
}
