<?php

namespace App\Services\Brochure;

use Illuminate\Support\Str;

class PageContentLimiter
{
    public function cards(array $cards): array
    {
        return collect($cards)->map(fn ($card) => [
            'title' => $this->shortText($card['title'] ?? null, 44),
            'description' => $this->shortText($card['description'] ?? null, 120),
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

        return mb_strlen(trim(strip_tags((string) $html))) <= $limit ? $html : e($plain);
    }

    public function shortText(?string $value, int $limit): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
        // The bundled PDF fonts do not contain emoji glyphs; remove them before
        // rendering so a brochure never shows missing-character squares.
        $text = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $text);

        return $text === '' ? null : Str::limit($text, $limit, '');
    }
}
