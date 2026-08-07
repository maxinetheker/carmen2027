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

    public function test_it_exposes_the_croquis_as_a_data_uri(): void
    {
        // dompdf does not render inline <svg>: it drops every shape and reflows the
        // <text> nodes as ordinary HTML, which turned the croquis into a paragraph of
        // loose street names. Only the data-URI form goes through php-svg-lib.
        $uri = (new SvgSanitizer)->dataUri('<svg viewBox="0 0 10 10"><rect width="5" height="5"/></svg>');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);
        $this->assertStringContainsString('<rect', base64_decode(substr($uri, 26)));
    }

    public function test_the_root_always_carries_a_namespace_and_dimensions(): void
    {
        // php-svg-lib needs both to parse and scale the image.
        $result = (new SvgSanitizer)->sanitize('<svg viewBox="0 0 560 310"><rect width="5" height="5"/></svg>');

        $this->assertStringContainsString('xmlns="http://www.w3.org/2000/svg"', $result);
        $this->assertStringContainsString('width="560"', $result);
        $this->assertStringContainsString('height="310"', $result);
    }

    public function test_a_missing_viewbox_falls_back_to_the_expected_canvas(): void
    {
        $result = (new SvgSanitizer)->sanitize('<svg><rect width="5" height="5"/></svg>');

        $this->assertStringContainsString('viewBox="0 0 560 310"', $result);
    }

    public function test_data_uri_is_null_when_there_is_no_usable_svg(): void
    {
        $this->assertNull((new SvgSanitizer)->dataUri(null));
        $this->assertNull((new SvgSanitizer)->dataUri('lo siento, no pude generar el croquis'));
    }
}
