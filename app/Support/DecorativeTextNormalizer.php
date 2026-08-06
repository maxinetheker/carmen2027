<?php

namespace App\Support;

use DOMElement;
use DOMNode;

final class DecorativeTextNormalizer
{
    public function normalize(DOMNode $node): bool
    {
        $text = (string) $node->nodeValue;
        if (! class_exists(\Normalizer::class)
            || ! preg_match('/[\x{1D400}-\x{1D7FF}]/u', $text)) {
            return false;
        }
        $parent = $node->parentNode;
        if (! $parent || ! $node->ownerDocument) {
            return false;
        }
        if ($parent instanceof DOMElement
            && in_array(strtolower($parent->tagName), ['strong', 'b', 'h2', 'h3', 'h4'], true)) {
            $node->nodeValue = \Normalizer::normalize($text, \Normalizer::FORM_KC) ?: $text;

            return false;
        }

        $parts = preg_split(
            '/([\x{1D400}-\x{1D7FF}][\x{1D400}-\x{1D7FF}\p{M}]*)/u',
            $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        foreach ($parts ?: [$text] as $part) {
            $clean = str_replace(
                ["\u{00A0}", "\u{202F}", "\u{2060}", "\u{FEFF}"],
                [' ', ' ', '', ''], $part
            );
            if (preg_match('/[\x{1D400}-\x{1D7FF}]/u', $part)) {
                $strong = $node->ownerDocument->createElement('strong');
                $strong->appendChild($node->ownerDocument->createTextNode(
                    \Normalizer::normalize($clean, \Normalizer::FORM_KC) ?: $clean
                ));
                $parent->insertBefore($strong, $node);
            } else {
                $parent->insertBefore($node->ownerDocument->createTextNode($clean), $node);
            }
        }
        $parent->removeChild($node);

        return true;
    }
}
