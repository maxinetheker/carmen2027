<?php

namespace App\Services\Import;

use App\Support\ScrapedNumber;
use DOMDocument;
use DOMXPath;

/**
 * Traduce una ficha de remax.pe a los campos del CRM.
 *
 * La página viene renderizada en el servidor con clases propias muy estables
 * (`titulo_01`, `__bodyt`/`__bodyc`, `sp-image`), así que se apunta a esas en vez
 * de a la posición de los elementos, que sí cambia con cada rediseño.
 */
class RemaxPropertyParser
{
    public function __construct(private RemaxPageReader $reader)
    {
    }

    public function parse(string $html, ?string $sourceUrl = null): array
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        $xpath = new DOMXPath($document);

        $badge = $this->reader->text($xpath, "//*[contains(@class,'badge-blue')]");
        $facts = $this->reader->facts($xpath);
        [$pen, $usd] = $this->reader->prices($xpath);
        $location = $this->reader->location($xpath);

        return [
            'source_url' => $sourceUrl,
            'external_id' => $this->reader->externalId($xpath),
            'title' => $this->reader->text($xpath, "//*[contains(@class,'titulo_01')]//h1") ?: null,
            'type' => $this->type($badge),
            'operation' => str_contains(mb_strtoupper($badge), 'ALQUILER') ? 'alquiler' : 'venta',
            'badge' => $badge ?: null,
            'district' => $location['district'],
            'address' => $location['full'],
            'currency' => $usd ? 'USD' : 'PEN',
            'price' => $usd ?: $pen,
            'price_pen' => $pen,
            'price_usd' => $usd,
            'area' => $this->area($facts),
            'bedrooms' => (int) ScrapedNumber::decimal($facts['Habitaciones'] ?? ''),
            'bathrooms' => $this->bathrooms($facts),
            'description' => $this->reader->description($xpath),
            'images' => $this->reader->images($xpath),
            'features' => $this->features($facts),
        ] + $this->reader->coordinates($html);
    }

    /** Se prefiere el área construida; el terreno es el respaldo para casas y lotes. */
    private function area(array $facts): ?float
    {
        return ScrapedNumber::decimal($facts['Área Construida'] ?? $facts['Area Construida'] ?? '')
            ?: ScrapedNumber::decimal($facts['Área Terreno'] ?? $facts['Area Terreno'] ?? '');
    }

    private function bathrooms(array $facts): float
    {
        $full = (float) ScrapedNumber::decimal($facts['Baños'] ?? $facts['Banos'] ?? '');
        $half = (float) ScrapedNumber::decimal($facts['1/2 Baños'] ?? $facts['1/2 Banos'] ?? '');

        return $full + $half * 0.5;
    }

    private function features(array $facts): array
    {
        $wanted = ['Área Terreno' => 'square_foot', 'Área Construida' => 'square_foot',
            'Medidas' => 'straighten', 'Antigüedad' => 'apartment', 'N° de Pisos' => 'stairs',
            'Cocheras' => 'garage', 'Servicio de Agua' => 'water_drop',
            'Energia Electrica' => 'electric_bolt', 'Servicio de Gas' => 'room_service'];

        $features = [];
        foreach ($facts as $label => $value) {
            $icon = $wanted[$label] ?? null;
            if ($icon && $value !== '' && $value !== '0') {
                $features[] = ['icon' => $icon, 'label' => $label, 'value' => $value];
            }
        }

        return $features;
    }

    private function type(string $badge): string
    {
        return match (true) {
            str_contains($badge = mb_strtoupper($badge), 'DEPARTAMENTO') => 'departamento',
            str_contains($badge, 'TERRENO'), str_contains($badge, 'LOTE') => 'terreno',
            str_contains($badge, 'OFICINA') => 'oficina',
            str_contains($badge, 'LOCAL'), str_contains($badge, 'INDUSTRIAL'),
            str_contains($badge, 'COMERCIAL') => 'local',
            default => 'casa',
        };
    }
}
