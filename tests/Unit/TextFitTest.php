<?php

namespace Tests\Unit;

use App\Services\Brochure\TextFit;
use PHPUnit\Framework\TestCase;

class TextFitTest extends TestCase
{
    private array $tiers;

    protected function setUp(): void
    {
        $this->tiers = [
            ['max' => 10, 'size' => '26pt'],
            ['max' => 20, 'size' => '22pt'],
            ['max' => 30, 'size' => '18pt'],
        ];
    }

    public function test_it_picks_the_largest_tier_that_still_fits(): void
    {
        $this->assertSame('26pt', TextFit::size('Corto', $this->tiers));
    }

    public function test_it_shrinks_for_longer_text(): void
    {
        $this->assertSame('22pt', TextFit::size(str_repeat('a', 15), $this->tiers));
    }

    public function test_it_falls_back_to_the_smallest_tier_when_text_exceeds_every_max(): void
    {
        $this->assertSame('18pt', TextFit::size(str_repeat('a', 999), $this->tiers));
    }

    public function test_it_treats_a_null_text_as_empty(): void
    {
        $this->assertSame('26pt', TextFit::size(null, $this->tiers));
    }

    public function test_to_width_shrinks_as_the_text_grows(): void
    {
        $short = (float) TextFit::toWidth('Asesora', 80);
        $real = (float) TextFit::toWidth('Carmen Mestanza Inmobiliaria · Agente Inmobiliario', 80);
        $long = (float) TextFit::toWidth(str_repeat('a', 90), 80);

        $this->assertGreaterThan($real, $short);
        $this->assertGreaterThan($long, $real);
    }

    public function test_to_width_never_leaves_the_legible_range(): void
    {
        // Cover footers must stay on one line, but not at any cost: a runaway string
        // clamps at the floor rather than rendering type nobody can read.
        $this->assertSame('9pt', TextFit::toWidth('a', 80));
        $this->assertSame('6pt', TextFit::toWidth(str_repeat('a', 500), 80));
        $this->assertSame('12pt', TextFit::toWidth('a', 82, max: 12, min: 8.5));
        $this->assertSame('8.5pt', TextFit::toWidth(str_repeat('a', 500), 82, max: 12, min: 8.5));
    }

    public function test_to_width_keeps_measured_real_content_on_one_line(): void
    {
        // Measured from a rendered brochure: 49 bold characters spanned 73.8mm at 7.5pt.
        // The size chosen for that string must not imply a wider line than the column.
        $text = 'Carmen Mestanza Inmobiliaria · Agente Inmobiliario';
        $size = (float) TextFit::toWidth($text, 80);

        $this->assertLessThanOrEqual(80, mb_strlen($text) * 0.2008 * $size);
    }

    public function test_a_wider_column_allows_a_bigger_size(): void
    {
        // Wide clamps so the comparison measures the fit itself, not the 6–9pt ceiling.
        $narrow = (float) TextFit::toWidth('Carmen Mestanza Inmobiliaria', 60, max: 40, min: 1);
        $wide = (float) TextFit::toWidth('Carmen Mestanza Inmobiliaria', 100, max: 40, min: 1);

        $this->assertGreaterThan($narrow, $wide);
    }
}
