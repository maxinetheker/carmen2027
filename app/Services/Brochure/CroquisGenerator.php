<?php

namespace App\Services\Brochure;

use App\Models\Property;
use App\Services\Ai\AiSettings;
use App\Services\Ai\OpenAiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Draws a simplified inline-SVG location sketch. Requires a real visual reference
 * (an uploaded screenshot and/or a Google Static Maps fetch of the property's own
 * registered coordinates) — with no reference image at all we skip the section
 * entirely rather than let the model invent a street layout it can't verify.
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

        if (! ($options['croquis_enabled'] ?? false)) {
            return ['svg' => null, 'usage' => $emptyUsage];
        }

        if (! $property->latitude || ! $property->longitude) {
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
