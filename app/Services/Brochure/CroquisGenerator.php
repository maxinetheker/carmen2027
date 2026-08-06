<?php

namespace App\Services\Brochure;

use App\Models\Property;
use App\Services\Ai\AiSettings;
use App\Services\Ai\OpenAiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Draws a simplified inline-SVG location sketch. "Automático" fetches a Google
 * Static Maps image of the property's own registered coordinates via PHP (no
 * upload needed); "Manual" uses an uploaded screenshot instead (still combined
 * with the auto-fetched map when available, for extra context). With no reference
 * image at all we skip the section rather than let the model invent a street layout.
 */
class CroquisGenerator
{
    public function __construct(
        private OpenAiClient $ai,
        private VisionImageEncoder $encoder,
        private SvgSanitizer $sanitizer,
        private AiSettings $aiSettings,
    ) {}

    /**
     * @return array{svg:?string,usage:array}
     */
    public function generate(Property $property, array $options, string $accentColor): array
    {
        $emptyUsage = ['input_tokens' => 0, 'output_tokens' => 0, 'cached_tokens' => 0];
        $mode = $options['croquis_mode'] ?? 'off';

        if ($mode === 'off') {
            return ['svg' => null, 'usage' => $emptyUsage];
        }

        $references = $this->collectReferenceImages($property, $options);
        if (! $references) {
            return ['svg' => null, 'usage' => $emptyUsage];
        }

        $prompt = "Ubicación real: {$property->address}, {$property->district}, Lima, Perú. "
            ."Coordenadas GPS: {$property->latitude}, {$property->longitude}. "
            .'Observa la(s) imagen(es) de referencia (captura de mapa) adjuntas y dibuja un croquis '
            .'esquemático simplificado en SVG (viewBox="0 0 560 310") en el estilo de un plano técnico: '
            .'calles principales visibles en la imagen como rectángulos/líneas con su nombre si es legible, '
            .'un marcador o polígono resaltado para la ubicación de la propiedad, y una rosa de los vientos. '
            .'Usa el color de acento '.$accentColor.' para resaltar la propiedad. No agregues calles, '
            .'comercios o referencias que no puedas verificar en la imagen entregada — si no se distingue '
            .'con claridad, omítelo en vez de inventarlo. Responde solo con el bloque <svg>...</svg>, sin texto '
            .'adicional.';

        if (! $property->latitude || ! $property->longitude) {
            $prompt .= ' No hay coordenadas GPS registradas: usa únicamente la captura de mapa adjunta como referencia.';
        }
        $prompt .= ' El SVG se incrustará como HTML seguro en el PDF; conserva únicamente elementos SVG compatibles.';
        $instructions = PromptContext::instructions($this->aiSettings->basePrompt(), $options['extra_prompt'] ?? null);
        $result = $this->ai->vision($prompt, $references, null, $instructions);
        $svg = $this->sanitizer->sanitize($result['text'] ?? null);

        return ['svg' => $svg, 'usage' => $result['usage'] ?? $emptyUsage];
    }

    /**
     * @return string[] data: URIs
     */
    private function collectReferenceImages(Property $property, array $options): array
    {
        $references = [];

        if (! empty($options['croquis_reference_path'])) {
            try {
                $references[] = $this->encoder->fromDisk('local', $options['croquis_reference_path'], 512);
            } catch (\Throwable $e) {
                Log::warning('No se pudo leer la imagen de referencia del croquis', ['error' => $e->getMessage()]);
            }
        }

        if ($static = $this->fetchStaticMap($property)) {
            $references[] = $static;
        }

        return $references;
    }

    private function fetchStaticMap(Property $property): ?string
    {
        if (! $property->latitude || ! $property->longitude) {
            return null;
        }

        $key = config('services.google_maps.key');
        if (! $key) {
            return null;
        }

        try {
            $response = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/staticmap', [
                'center' => "{$property->latitude},{$property->longitude}",
                'zoom' => 17,
                'size' => '640x400',
                'maptype' => 'roadmap',
                'markers' => "color:red|{$property->latitude},{$property->longitude}",
                'key' => $key,
            ]);

            if ($response->failed()) {
                return null;
            }

            return $this->encoder->encode($response->body(), 512);
        } catch (\Throwable $e) {
            Log::warning('No se pudo obtener el mapa estático de Google para el croquis', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
