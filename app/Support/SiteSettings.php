<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\Property;
use App\Models\SiteSetting;

/**
 * Valores de la portada que antes estaban escritos a mano en el controlador.
 * Ahora salen de «Editar sitio web», con estos números como punto de partida
 * cuando todavía no se ha guardado nada.
 */
class SiteSettings
{
    public const DEFAULT_YEARS = 6;

    public const DEFAULT_CLIENTS = 48;

    public const DEFAULT_CERTIFICATIONS_TITLE = 'Certificaciones';

    /** @return array{years: int, clients: int, properties: int} */
    public static function stats(array $settings): array
    {
        $clients = self::number($settings, 'stats_clients', self::DEFAULT_CLIENTS);

        return [
            'years' => self::number($settings, 'stats_years', self::DEFAULT_YEARS),
            // Los cierres ganados en el CRM se suman al número base para que la
            // cifra crezca sola sin tener que editarla cada mes.
            'clients' => $clients + Lead::where('status', 'won')->count(),
            'properties' => Property::published()->count(),
        ];
    }

    private static function number(array $settings, string $key, int $fallback): int
    {
        $value = $settings[$key] ?? null;

        return is_numeric($value) ? max(0, (int) $value) : $fallback;
    }

    public static function certificationsTitle(array $settings): string
    {
        return trim((string) ($settings['certifications_title'] ?? ''))
            ?: self::DEFAULT_CERTIFICATIONS_TITLE;
    }

    /** @return array<string, string> Todo el sitio lee los ajustes por aquí. */
    public static function all(): array
    {
        return SiteSetting::values();
    }
}
