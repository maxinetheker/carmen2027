<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

final class RichTextSanitizer
{
    private const ALLOWED = [
        'p', 'div', 'br', 'strong', 'b', 'em', 'i', 'u',
        'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'a',
    ];

    public function __construct(private DecorativeTextNormalizer $decorative)
    {
    }

    public function clean(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }

        // Conserva el HTML visual y Unicode original para que web y Android muestren
        // exactamente el mismo contenido; solo se retiran elementos ejecutables inseguros.
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8"?><div id="rich-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $document->getElementById('rich-root');
        if (! $root) {
            return null;
        }

        $this->sanitizeChildren($root);
        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result) ?: null;
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($node);
                continue;
            }
            if ($node->nodeType === XML_TEXT_NODE) {
                if ($this->decorative->normalize($node)) {
                    continue;
                }
                $node->nodeValue = str_replace(
                    ["\u{00A0}", "\u{202F}", "\u{2060}", "\u{FEFF}"],
                    [' ', ' ', '', ''],
                    (string) $node->nodeValue
                );
                continue;
            }
            if (! $node instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($node->tagName);
            if ($tag === 'img') {
                $this->replaceEmojiImage($node);
                continue;
            }
            if (in_array($tag, ['script', 'style', 'iframe', 'object'], true)) {
                $parent->removeChild($node);
                continue;
            }
            $this->sanitizeChildren($node);
            if (! in_array($tag, self::ALLOWED, true)) {
                $this->unwrap($node);
                continue;
            }
            $this->sanitizeAttributes($node, $tag);
        }
    }

    private function replaceEmojiImage(DOMElement $element): void
    {
        $alt = trim($element->getAttribute('alt'));
        $isEmoji = mb_strlen($alt) <= 32 && preg_match(
            '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $alt
        );
        $replacement = $isEmoji && $element->ownerDocument
            ? $element->ownerDocument->createTextNode($alt) : null;

        if ($replacement) {
            $element->parentNode?->replaceChild($replacement, $element);
        } else {
            $element->parentNode?->removeChild($element);
        }
    }

    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $href = $tag === 'a' ? trim($element->getAttribute('href')) : '';
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttribute($attribute->name);
        }
        if ($tag !== 'a' || ! preg_match('#^(https?://|mailto:|tel:|/|\\#)#i', $href)) {
            return;
        }
        $element->setAttribute('href', $href);
        if (preg_match('#^https?://#i', $href)) {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }
        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }
}
