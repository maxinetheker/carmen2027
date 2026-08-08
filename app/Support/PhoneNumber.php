<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Comparable form of a phone number. The same person is saved as "987 654 321",
     * "+51 987654321" or "0051987654321" depending on where the number came from
     * (formulario web, agenda del celular, registro de llamadas), so matching keeps
     * only the last nine digits — la longitud de un número peruano sin prefijo.
     */
    public static function key(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
        if (mb_strlen($digits) < 6) {
            return null;
        }

        return mb_substr($digits, -9);
    }

    /** Human friendly version used in emails and exports. */
    public static function pretty(?string $number): string
    {
        $number = trim((string) $number);
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        return mb_strlen($digits) === 9
            ? trim(mb_substr($digits, 0, 3).' '.mb_substr($digits, 3, 3).' '.mb_substr($digits, 6))
            : ($number ?: '—');
    }
}
