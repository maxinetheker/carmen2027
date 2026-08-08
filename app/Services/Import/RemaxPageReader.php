<?php

namespace App\Services\Import;

use App\Support\ScrapedNumber;
use DOMXPath;

/**
 * Extracción cruda de la ficha de remax.pe: los trozos sueltos de la página antes
 * de convertirse en campos del CRM. Va aparte del parser para que cada archivo
 * cuente una sola cosa —de dónde sale el dato, o en qué se convierte.
 */
class RemaxPageReader
{
    /** Pares etiqueta/valor de los bloques MEDIDAS y CARACTERÍSTICAS. */
    public function facts(DOMXPath $xpath): array
    {
        $facts = [];
        foreach ($xpath->query("//*[contains(@class,'__bodyt')]") as $label) {
            $value = $xpath->query("following::*[contains(@class,'__bodyc')][1]", $label)->item(0);
            $name = $this->clean($label->textContent);
            if ($name !== '' && $value) {
                $facts[$name] = $this->clean($value->textContent);
            }
        }

        return $facts;
    }

    /** @return array{0: float|null, 1: float|null} soles y dólares */
    public function prices(DOMXPath $xpath): array
    {
        $pen = $usd = null;
        foreach ($xpath->query("//*[contains(@class,'titulo_04')]//li") as $item) {
            $text = $this->clean($item->textContent);
            if (str_contains($text, 'S/')) {
                $pen ??= ScrapedNumber::decimal($text);
            } elseif (str_contains(mb_strtoupper($text), 'USD') || str_contains($text, '$')) {
                $usd ??= ScrapedNumber::decimal($text);
            }
        }

        return [$pen, $usd];
    }

    /** "Lima, Lima, San Isidro" → el distrito es siempre el último tramo. */
    public function location(DOMXPath $xpath): array
    {
        $raw = $this->text($xpath, "//*[contains(@class,'titulo_07')]//h5");
        $parts = array_values(array_filter(array_map('trim', explode(',', $raw))));

        return ['district' => $parts ? end($parts) : null, 'full' => $raw ?: null];
    }

    public function description(DOMXPath $xpath): ?string
    {
        $node = $xpath->query("//*[contains(@class,'__text_match')]")->item(0);
        if (! $node) {
            return null;
        }
        $text = preg_replace("/\r\n?/", "\n", $node->textContent);

        return trim(preg_replace("/\n{3,}/", "\n\n", (string) $text)) ?: null;
    }

    /** @return string[] */
    public function images(DOMXPath $xpath): array
    {
        $urls = [];
        foreach ($xpath->query("//img[contains(@class,'sp-image')]") as $image) {
            foreach (['data-large', 'data-medium', 'data-src', 'src'] as $attribute) {
                $url = $image->getAttribute($attribute);
                if ($url && str_starts_with($url, 'http')) {
                    // La firma de S3 cambia en cada carga; la ruta sin query es lo
                    // único estable para descartar la misma foto repetida.
                    $urls[strtok($url, '?')] = $url;
                    break;
                }
            }
        }

        return array_values($urls);
    }

    public function externalId(DOMXPath $xpath): ?string
    {
        $badge = $this->text($xpath, "//*[contains(@class,'badge-danger')]");

        return preg_match('/(\d{4,})/', $badge, $match) ? $match[1] : null;
    }

    /** El mapa de la ficha lleva las coordenadas dentro de la llamada a Leaflet. */
    public function coordinates(string $html): array
    {
        return preg_match('/setView\(\s*\[\s*(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/', $html, $match)
            ? ['latitude' => (float) $match[1], 'longitude' => (float) $match[2]]
            : ['latitude' => null, 'longitude' => null];
    }

    public function text(DOMXPath $xpath, string $query): string
    {
        return $this->clean($xpath->query($query)->item(0)?->textContent ?? '');
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($value)) ?? '');
    }
}
