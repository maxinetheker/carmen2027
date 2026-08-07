<?php

namespace App\Services\Brochure;

use Normalizer;

/**
 * Keeps brochure text to glyphs the bundled PDF font (DejaVu Sans) can actually draw.
 *
 * Listings copied from WhatsApp/Facebook routinely carry "styled" Unicode — bold or
 * italic maths letters such as U+1D5D5 (𝗕), fullwidth forms, circled digits. DejaVu has
 * no glyph for any of them, so dompdf drew empty boxes: a property title came out as a
 * row of tofu in the ficha técnica. Compatibility-folding (NFKC) turns 𝗕𝗨𝗦 back into
 * BUS, and whatever is still outside the font's coverage is dropped rather than shown
 * as a box. Folding is applied per character so plain text (and m², nº, ½) is untouched.
 */
class GlyphNormalizer
{
    /** Decorative blocks that fold cleanly to ASCII under NFKC. */
    private const DECORATIVE = '/[\x{1D400}-\x{1D7FF}\x{FF01}-\x{FF5E}\x{2460}-\x{24FF}\x{1F100}-\x{1F1FF}\x{2100}-\x{214F}]/u';

    /** Emoji and pictographs: no glyph in DejaVu and nothing meaningful to fold to. */
    private const PICTOGRAPHS = '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE00}-\x{FE0F}\x{200D}]/u';

    /** Everything DejaVu Sans covers that this project can plausibly need. */
    private const RENDERABLE = '/[^\x{0009}\x{000A}\x{0020}-\x{024F}\x{0370}-\x{04FF}\x{2000}-\x{206F}\x{20A0}-\x{20BF}\x{2190}-\x{21FF}\x{2200}-\x{22FF}\x{25A0}-\x{25FF}]/u';

    public function clean(?string $text): string
    {
        $text = (string) $text;
        if ($text === '') {
            return '';
        }

        $text = preg_replace_callback(
            self::DECORATIVE,
            static fn (array $match): string => Normalizer::normalize($match[0], Normalizer::FORM_KC) ?: '',
            $text
        ) ?? $text;

        $text = preg_replace(self::PICTOGRAPHS, '', $text) ?? $text;
        $text = preg_replace(self::RENDERABLE, '', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
