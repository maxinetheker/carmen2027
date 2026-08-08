<?php

namespace App\Support;

class ScrapedNumber
{
    /**
     * Primer número de un texto scrapeado, en formato peruano.
     *
     * Los portales escriben "S/. 1'134,000.00", "209.00 m2" o "10 Años": el
     * apóstrofo y la coma son separadores de miles y el punto es el decimal.
     */
    public static function decimal(?string $text): ?float
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        $normalized = str_replace(["'", ',', ' ', ' '], '', $text);
        if (! preg_match('/-?\d+(?:\.\d+)?/', $normalized, $match)) {
            return null;
        }

        return (float) $match[0];
    }
}
