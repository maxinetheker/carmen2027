<?php

namespace App\Services\Brochure;

use Illuminate\Support\Str;

class PageContentLimiter
{
    public function __construct(private GlyphNormalizer $glyphs) {}

    public function cards(array $cards): array
    {
        // Above the budget the prompt asks for (38/135), so a card written to spec is
        // never cut and only a runaway one is trimmed. The box grew to match.
        return collect($cards)->map(fn ($card) => [
            'title' => $this->shortText($card['title'] ?? null, 46),
            'description' => $this->shortText($card['description'] ?? null, 150),
        ])->filter(fn ($card) => $card['title'] && $card['description'])->take(3)->values()->all();
    }

    public function faqs(array $faqs, int $limit, int $answerLimit): array
    {
        return collect($faqs)->map(fn ($faq) => [
            'question' => $this->shortText($faq['question'] ?? null, 95),
            'answer' => $this->shortText($faq['answer'] ?? null, $answerLimit),
        ])->filter(fn ($faq) => $faq['question'] && $faq['answer'])->take($limit)->values()->all();
    }

    public function facts(array $facts, int $valueLimit): array
    {
        return array_map(fn ($fact) => [
            'label' => $this->shortText($fact['label'] ?? null, 42),
            'value' => $this->shortText($fact['value'] ?? null, $valueLimit),
        ], $facts);
    }

    public function stats(array $stats): array
    {
        return array_map(fn ($stat) => [
            'value' => $this->shortText($stat['value'] ?? null, 16),
            'label' => $this->shortText($stat['label'] ?? null, 58),
        ], $stats);
    }

    public function htmlExcerpt(?string $html, int $limit): ?string
    {
        $plain = $this->shortText($html, $limit);
        if (! $plain) return null;

        // Tags are ASCII, so normalising the markup only touches its text nodes —
        // the short-enough branch must be cleaned too or it keeps its tofu glyphs.
        return mb_strlen($this->glyphs->clean(strip_tags((string) $html))) <= $limit
            ? $this->glyphs->clean((string) $html)
            : e($plain);
    }

    public function shortText(?string $value, int $limit): ?string
    {
        // Folds styled Unicode and drops anything the PDF font cannot draw, so a
        // brochure never shows missing-character squares.
        $text = $this->glyphs->clean(strip_tags((string) $value));

        // preserveWords + an ellipsis: cutting at an exact character count left
        // paragraphs ending mid-word ("… distribución, manufac") with no sign that
        // anything had been removed.
        return $text === '' ? null : Str::limit($text, $limit, '…', preserveWords: true);
    }
}
