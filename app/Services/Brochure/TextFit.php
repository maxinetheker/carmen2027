<?php

namespace App\Services\Brochure;

/**
 * Picks a font-size tier by character count so AI-written or hand-written text never
 * overflows a template's fixed-height box, regardless of how long it turns out to be.
 */
class TextFit
{
    /**
     * @param  array<int,array{max:int,size:string}>  $tiers  ordered from largest to smallest size;
     *                                                        `max` is the highest character count that tier still fits.
     */
    public static function size(?string $text, array $tiers): string
    {
        $length = mb_strlen((string) $text);
        foreach ($tiers as $tier) {
            if ($length <= $tier['max']) {
                return $tier['size'];
            }
        }

        return end($tiers)['size'];
    }

    /**
     * Largest font size at which `$text` still fits on ONE line inside a column of
     * `$columnMm`, so a footer keeps its shape when the agency later edits its e-mail,
     * job title or name into something longer.
     *
     * The 0.2008 factor is measured, not guessed: a rendered brochure put 49 characters
     * of bold DejaVu Sans across 73.8mm at 7.5pt, i.e. ~0.2mm of width per character per
     * point of size. Bold is the widest case here, so regular text gets extra slack.
     */
    public static function toWidth(string $text, float $columnMm, float $max = 9, float $min = 6): string
    {
        $characters = max(1, mb_strlen(trim($text)));
        $size = ($columnMm * 0.96) / ($characters * 0.2008);

        return round(max($min, min($max, $size)), 1).'pt';
    }
}
