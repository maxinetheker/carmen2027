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
}
