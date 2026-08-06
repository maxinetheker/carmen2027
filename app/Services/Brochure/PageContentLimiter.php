<?php

namespace App\Services\Brochure;

use Illuminate\Support\Str;

class PageContentLimiter
{
    public function cards(array $cards): array
    {
        return collect($cards)->map(fn ($card) => [
            'title' => $this->shortText($card['title'] ?? null, 52),
            'description' => $this->shortText($card['description'] ?? null, 180),
        ])->filter(fn ($card) => $card['title'] && $card['description'])->take(3)->values()->all();
    }

    public function faqs(array $faqs, int $limit): array
    {
        return collect($faqs)->map(fn ($faq) => [
            'question' => $this->shortText($faq['question'] ?? null, 130),
            'answer' => $this->shortText($faq['answer'] ?? null, 280),
        ])->filter(fn ($faq) => $faq['question'] && $faq['answer'])->take($limit)->values()->all();
    }

    public function shortText(?string $value, int $limit): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)));

        return $text === '' ? null : Str::limit($text, $limit, '');
    }
}
