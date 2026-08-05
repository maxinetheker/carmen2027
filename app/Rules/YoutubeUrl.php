<?php

namespace App\Rules;

use App\Services\YoutubeUrlParser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YoutubeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') return;
        if (! app(YoutubeUrlParser::class)->id((string) $value)) {
            $fail('Ingresa un enlace válido de YouTube.');
        }
    }
}
