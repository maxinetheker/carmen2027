<?php

namespace App\Services\Brochure;

use App\Models\Property;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

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

    /**
     * Property features (already curated by the advisor), or basic attributes as a
     * fallback — always something real to show, regardless of what the AI returns.
     */
    public function specs(Property $property): array
    {
        $features = $property->features->take(6)
            ->map(fn ($f) => ['value' => $f->value, 'label' => $f->label])
            ->all();

        return $features ?: array_values(array_filter([
            ['value' => "{$property->area} m²", 'label' => 'Área total'],
            $property->bedrooms ? ['value' => (string) $property->bedrooms, 'label' => 'Dormitorios'] : null,
            ['value' => $property->bathrooms_label, 'label' => 'Baños'],
            ['value' => $property->type_label, 'label' => 'Tipo de propiedad'],
        ]));
    }

    public function fichaTecnica(Property $property): array
    {
        return array_values(array_filter([
            ['label' => 'Inmueble', 'value' => $property->title],
            ['label' => 'Código', 'value' => $property->code],
            $property->address ? ['label' => 'Dirección', 'value' => $property->address] : null,
            ['label' => 'Distrito', 'value' => $property->district],
            ['label' => 'Área', 'value' => "{$property->area} m²"],
            ['label' => 'Tipo', 'value' => "{$property->type_label} en {$property->operation_label}"],
            ['label' => 'Precio', 'value' => $this->priceMain($property).' ('.$this->priceSub($property).')'],
        ]));
    }

    /**
     * Footer copy, with generous ceilings rather than tight ones.
     *
     * The footers now size their own text to the width available (see TextFit::toWidth),
     * so length is handled by shrinking rather than cutting. These limits only exist to
     * stop absurd input; contact details in particular are left long enough to survive
     * intact, because a truncated e-mail or phone number is worse than small type — you
     * cannot write to "carmen.contacto@remaxintegri…".
     */
    public function agent(): array
    {
        $settings = SiteSetting::values();
        $clamp = fn (?string $value, int $limit) => $value
            ? Str::limit(trim($value), $limit, '…', preserveWords: true)
            : null;

        return [
            'name' => $clamp(config('app.name', 'Carmen Mestanza'), 48),
            'role' => $clamp($settings['ceo_title'] ?? 'Asesora Inmobiliaria', 52),
            'address' => $clamp($settings['service_area'] ?? null, 74),
            // Never shortened in practice: contact details must stay usable.
            'phone' => $clamp($settings['phone'] ?? $settings['whatsapp'] ?? null, 30),
            'email' => $clamp($settings['email'] ?? null, 70),
            'website' => preg_replace('#^https?://(www\.)?#', '', (string) config('app.url')),
        ];
    }
}
