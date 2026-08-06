<?php

namespace Tests\Unit;

use App\Services\Brochure\SvgSanitizer;
use PHPUnit\Framework\TestCase;

class SvgSanitizerTest extends TestCase
{
    public function test_it_keeps_a_well_formed_svg(): void
    {
        $svg = '<svg viewBox="0 0 100 100"><rect x="0" y="0" width="10" height="10" fill="#000"/></svg>';

        $result = (new SvgSanitizer)->sanitize($svg);

        $this->assertNotNull($result);
        $this->assertStringContainsString('<rect', $result);
    }

    public function test_it_strips_script_tags(): void
    {
        $svg = '<svg viewBox="0 0 100 100"><script>alert(1)</script><rect width="10" height="10"/></svg>';

        $result = (new SvgSanitizer)->sanitize($svg);

        $this->assertStringNotContainsString('script', $result);
        $this->assertStringNotContainsString('alert', $result);
    }

    public function test_it_strips_event_handler_attributes(): void
    {
        $svg = '<svg viewBox="0 0 100 100"><rect width="10" height="10" onclick="alert(1)"/></svg>';

        $result = (new SvgSanitizer)->sanitize($svg);

        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('alert', $result);
    }

    public function test_it_extracts_the_svg_fragment_from_surrounding_prose(): void
    {
        $wrapped = "Here is the sketch:\n<svg viewBox=\"0 0 10 10\"><rect width=\"5\" height=\"5\"/></svg>\nHope that helps!";

        $result = (new SvgSanitizer)->sanitize($wrapped);

        $this->assertStringStartsWith('<svg', $result);
    }

    public function test_it_returns_null_for_non_svg_input(): void
    {
        $this->assertNull((new SvgSanitizer)->sanitize('not an svg at all'));
        $this->assertNull((new SvgSanitizer)->sanitize(null));
    }
}
