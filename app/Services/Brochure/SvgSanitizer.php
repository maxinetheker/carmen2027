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
        $this->normalizeRoot($dom->documentElement);

        return $dom->saveXML($dom->documentElement) ?: null;
    }

    /**
     * Data URI of the sanitized markup, which is the only form dompdf actually draws.
     *
     * Inline <svg> is NOT rendered by dompdf: it silently drops every shape and reflows
     * the <text> nodes as ordinary HTML, which is why a croquis came out as a paragraph
     * of loose street names. The same markup inside <img src="data:image/svg+xml;..."/>
     * goes through php-svg-lib and renders properly.
     */
    public function dataUri(?string $svg): ?string
    {
        $clean = $this->sanitize($svg);

        return $clean ? 'data:image/svg+xml;base64,'.base64_encode($clean) : null;
    }

    /** php-svg-lib needs the namespace, and intrinsic dimensions to scale against. */
    private function normalizeRoot(\DOMElement $root): void
    {
        $root->setAttribute('xmlns', 'http://www.w3.org/2000/svg');

        $viewBox = preg_split('/[\s,]+/', trim($root->getAttribute('viewBox'))) ?: [];
        if (count($viewBox) !== 4) {
            $root->setAttribute('viewBox', '0 0 560 310');
            $viewBox = ['0', '0', '560', '310'];
        }

        if (! $root->getAttribute('width') || ! $root->getAttribute('height')) {
            $root->setAttribute('width', (string) (float) $viewBox[2]);
            $root->setAttribute('height', (string) (float) $viewBox[3]);
        }
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
