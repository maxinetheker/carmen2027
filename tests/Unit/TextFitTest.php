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
}
