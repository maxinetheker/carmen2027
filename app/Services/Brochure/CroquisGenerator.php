<?php

namespace App\Services\Brochure;

use App\Models\Property;
use App\Services\Ai\AiSettings;
use App\Services\Ai\OpenAiClient;
use Illuminate\Support\Facades\Log;

/**
 * Draws a simplified inline-SVG location sketch from a picture of the property's
 * surroundings.
 *
 * The reference image comes from OpenStreetMap by default: the server builds it from the
 * coordinates already stored on the property, so the croquis needs no clicks, no browser
 * permission and no API key. An advisor can additionally capture the Google embed in the
 * modal or attach a screenshot; when both exist both are sent, since more angles give the
 * model more to verify against. With no image at all the section is skipped and the
 * reason reported, rather than letting the model invent a street layout.
 */
class CroquisGenerator
{
    public function __construct(
        private OpenAiClient $ai,
        private VisionImageEncoder $encoder,
        private SvgSanitizer $sanitizer,
        private AiSettings $aiSettings,
        private OsmStaticMap $staticMap,
    ) {}

    /**
     * @return array{svg:?string,usage:array,warning:?string}
     */
    public function generate(Property $property, array $options, string $accentColor): array
    {
        $emptyUsage = ['input_tokens' => 0, 'output_tokens' => 0, 'cached_tokens' => 0];
        $mode = $options['croquis_mode'] ?? 'off';

        if ($mode === 'off') {
            return ['svg' => null, 'usage' => $emptyUsage, 'warning' => null];
        }

        // Returning null without a reason is what made a missing croquis invisible: the
        // page simply rendered without it and nobody knew why. Every give-up path below
        // now names its cause so the presentation row can show it.
        $references = $this->collectReferenceImages($property, $options);
        if (! $references) {
            return [
                'svg' => null,
                'usage' => $emptyUsage,
                'warning' => $this->missingReferenceReason($property, $options),
            ];
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

        return [
            'svg' => $svg,
            'usage' => $result['usage'] ?? $emptyUsage,
            'warning' => $svg ? null : 'La IA no devolvió un croquis válido a partir de la imagen entregada. Vuelve a intentarlo o usa una captura más nítida.',
        ];
    }

    /** Explains, in the advisor's terms, why no reference image could be assembled. */
    private function missingReferenceReason(Property $property, array $options): string
    {
        if (! $property->latitude || ! $property->longitude) {
            return 'No se generó el croquis: la propiedad no tiene ubicación marcada en el mapa. '
                .'Márcala en la ficha o adjunta una captura del mapa y vuelve a generar.';
        }

        return 'No se generó el croquis: no se pudo obtener la imagen del mapa para esas coordenadas. '
            .'Revisa la conexión del servidor o captura el mapa desde el formulario.';
    }

    /**
     * The automatic OpenStreetMap render of the property's own coordinates, plus whatever
     * capture the advisor sent, if any. The upload is listed first so the model sees the
     * deliberately-framed image before the generic one.
     *
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

        if ($property->latitude && $property->longitude) {
            $png = $this->staticMap->png((float) $property->latitude, (float) $property->longitude);
            if ($png) {
                $references[] = $this->encoder->encode($png, 640);
            }
        }

        return $references;
    }
}
