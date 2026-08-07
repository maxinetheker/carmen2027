<?php

namespace Tests\Unit;

use App\Services\Brochure\GlyphNormalizer;
use App\Services\Brochure\PageContentLimiter;
use PHPUnit\Framework\TestCase;

class PageContentLimiterTest extends TestCase
{
    private PageContentLimiter $limiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limiter = new PageContentLimiter(new GlyphNormalizer);
    }

    public function test_truncation_stops_at_a_word_boundary_and_marks_the_cut(): void
    {
        $text = 'Local con zonificación I2, 985 m² de área techada y 8 m de altura libre, '
            .'preparado para almacenes, distribución, manufactura y logística pesada.';

        $short = $this->limiter->shortText($text, 110);

        // The old Str::limit($text, $limit, '') sliced mid-word: "…, distribución, manufac".
        $this->assertStringEndsWith('…', $short);
        $this->assertDoesNotMatchRegularExpression('/\w…$/u', $short);
        $this->assertStringStartsWith('Local con zonificación I2', $short);
    }

    public function test_text_shorter_than_the_limit_is_left_alone(): void
    {
        $this->assertSame('Casa lista para habitar.', $this->limiter->shortText('Casa lista para habitar.', 110));
    }

    public function test_styled_unicode_is_folded_to_glyphs_the_pdf_font_can_draw(): void
    {
        // Mathematical bold capitals, as pasted from WhatsApp/Facebook listings. DejaVu
        // has no glyph for these, so dompdf drew a row of empty boxes in the ficha.
        $styled = "\u{00BF}\u{1D5D5}\u{1D5E8}\u{1D5E6}\u{1D5D6}\u{1D5D4}\u{1D5E6} un local?";

        $this->assertSame('¿BUSCAS un local?', $this->limiter->shortText($styled, 110));
    }

    public function test_emoji_and_unsupported_glyphs_are_removed(): void
    {
        $this->assertSame('Casa en Miraflores', $this->limiter->shortText("\u{1F3E0} Casa en Miraflores \u{2764}", 110));
    }

    public function test_accents_superscripts_and_separators_survive(): void
    {
        $this->assertSame(
            'Área 1005 m² · Jesús María · 50 % · €100',
            $this->limiter->shortText('Área 1005 m² · Jesús María · 50 % · €100', 110)
        );
    }

    public function test_cards_keep_titles_and_descriptions_within_their_box(): void
    {
        $cards = $this->limiter->cards([[
            'title' => str_repeat('Beneficio ', 10),
            'description' => str_repeat('Detalle útil de la propiedad. ', 10),
        ]]);

        $this->assertLessThanOrEqual(45, mb_strlen($cards[0]['title']));
        $this->assertLessThanOrEqual(111, mb_strlen($cards[0]['description']));
    }
}
