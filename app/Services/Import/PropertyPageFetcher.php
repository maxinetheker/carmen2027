<?php

namespace App\Services\Import;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Descarga la ficha pública de una propiedad.
 *
 * remax.pe está detrás de Cloudflare y puede responder con un desafío "Just a
 * moment…" a peticiones que vienen de un servidor. Cuando eso pasa no se puede
 * hacer nada desde aquí, así que se lanza un error explicando la alternativa:
 * abrir la página en el navegador y pegar el código fuente en el mismo modal.
 */
class PropertyPageFetcher
{
    public const SUPPORTED_HOSTS = ['remax.pe', 'www.remax.pe'];

    public function fetch(string $url): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                    .'(KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'es-PE,es;q=0.9',
            // Solo se reintenta cuando la conexión falla: si el portal responde con
            // un desafío anti-robots, insistir no cambia nada y demora la respuesta.
            ])->timeout(30)
                ->retry(2, 1000, fn ($exception) => $exception instanceof ConnectionException, false)
                ->get($url);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'No se pudo conectar con el portal. Revisa tu conexión e inténtalo otra vez.'
            );
        }

        $body = (string) $response->body();

        if ($this->isChallenge($response->status(), $body)) {
            throw new RuntimeException(
                'El portal bloqueó la descarga automática (protección anti-robots). '
                .'Abre la propiedad en tu navegador, guarda o copia el código fuente de la '
                .'página y pégalo en la pestaña «Pegar el código de la página» de este mismo cuadro.'
            );
        }

        if (! $response->successful() || mb_strlen($body) < 500) {
            throw new RuntimeException(
                'El portal respondió con un error ('.$response->status().'). '
                .'Verifica que el enlace sea el de la ficha de la propiedad.'
            );
        }

        return $body;
    }

    public function supports(string $url): bool
    {
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, self::SUPPORTED_HOSTS, true);
    }

    private function isChallenge(int $status, string $body): bool
    {
        return in_array($status, [403, 503], true)
            || str_contains($body, 'Just a moment')
            || str_contains($body, 'cf-browser-verification');
    }
}
