<?php

namespace App\Services\Brochure;

/**
 * Strict allow-list sanitizer for AI-generated inline SVG (the croquis), since that
 * markup gets embedded verbatim into a PDF-rendering HTML document.
 */
class SvgSanitizer
{
    private const ALLOWED_TAGS = [
        'svg', 'g', 'rect', 'circle', 'ellipse', 'line', 'polygon', 'polyline',
        'path', 'text', 'tspan', 'defs', 'lineargradient', 'radialgradient', 'stop',
    ];

    private const ALLOWED_ATTRS = [
        'viewbox', 'xmlns', 'width', 'height', 'fill', 'fill-opacity', 'stroke',
        'stroke-width', 'stroke-dasharray', 'stroke-linecap', 'stroke-linejoin',
        'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'points', 'd',
        'font-size', 'font-weight', 'font-family', 'text-anchor', 'transform',
        'class', 'id', 'offset', 'stop-color', 'stop-opacity', 'gradientunits',
        'opacity', 'letter-spacing',
    ];

    public function sanitize(?string $svg): ?string
    {
        if (! $svg || ! str_contains($svg, '<svg')) {
            return null;
        }

        // Isolate the <svg>...</svg> fragment in case the model wrapped it in prose/markdown.
        if (! preg_match('/<svg[\s\S]*<\/svg>/i', $svg, $match)) {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $loaded = $dom->loadXML($match[0], LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        $this->clean($dom->documentElement);

        return $dom->saveXML($dom->documentElement) ?: null;
    }

    private function clean(?\DOMNode $node): void
    {
        if (! $node instanceof \DOMElement) {
            return;
        }

        if (! in_array(strtolower($node->tagName), self::ALLOWED_TAGS, true)) {
            $node->parentNode?->removeChild($node);

            return;
        }

        foreach (iterator_to_array($node->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->name);
            if (str_starts_with($name, 'on') || ! in_array($name, self::ALLOWED_ATTRS, true)) {
                $node->removeAttribute($attribute->name);
            }
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->clean($child);
        }
    }
}
