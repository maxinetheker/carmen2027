<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Fechas escritas como las diría una persona. Los nombres van fijos en español en
 * vez de depender del locale de Carbon, que en este proyecto no está garantizado
 * y dejaría los avisos en inglés sin que nadie lo note.
 */
class HumanDate
{
    private const DAYS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom'];

    private const MONTHS = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun',
        'jul', 'ago', 'set', 'oct', 'nov', 'dic'];

    /** Ej.: "hoy 15:30", "mañana 09:00", "vie 12 set, 10:00". */
    public static function short(?Carbon $moment, ?string $timezone = null): string
    {
        if (! $moment) {
            return 'sin fecha';
        }
        $moment = $timezone ? $moment->copy()->timezone($timezone) : $moment->copy();
        $today = now($timezone ?? $moment->timezoneName)->startOfDay();
        $days = $today->diffInDays($moment->copy()->startOfDay(), false);

        $day = match (true) {
            $days === 0 => 'hoy',
            $days === 1 => 'mañana',
            $days === -1 => 'ayer',
            default => self::DAYS[(int) $moment->dayOfWeek].' '.$moment->day.' '
                .self::MONTHS[(int) $moment->month]
                .($moment->year !== $today->year ? ' '.$moment->year : ''),
        };

        return $day.' '.$moment->format('H:i');
    }

    /** Ej.: "en 25 minutos", "hace 2 días". */
    public static function distance(?Carbon $moment, ?string $timezone = null): string
    {
        if (! $moment) {
            return '—';
        }
        $now = now($timezone ?? $moment->timezoneName);
        $minutes = (int) round($now->diffInMinutes($moment, false));
        $past = $minutes < 0;
        $minutes = abs($minutes);

        $text = match (true) {
            $minutes < 1 => 'ahora mismo',
            $minutes < 60 => $minutes.' '.self::plural($minutes, 'minuto'),
            $minutes < 1440 => intdiv($minutes, 60).' '.self::plural(intdiv($minutes, 60), 'hora'),
            default => intdiv($minutes, 1440).' '.self::plural(intdiv($minutes, 1440), 'día'),
        };

        if ($text === 'ahora mismo') {
            return $text;
        }

        return $past ? "hace {$text}" : "en {$text}";
    }

    private static function plural(int $amount, string $word): string
    {
        return $amount === 1 ? $word : $word.'s';
    }
}
