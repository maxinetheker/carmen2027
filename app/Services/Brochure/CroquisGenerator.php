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
 * That picture always arrives from the browser: the presentation modal shows a keyless
 * Google Maps embed, and its capture button crops a frame of the tab down to the map.
 * An advisor can also attach a screenshot by hand. This project deliberately uses no
 * Google API key, so there is no server-side map download to fall back on — with no
 * reference image the section is skipped and the reason is reported, rather than
 * letting the model invent a street layout it cannot verify.
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
        if (empty($options['croquis_reference_path'])) {
            return $property->latitude && $property->longitude
                ? 'No se generó el croquis: no se adjuntó ninguna imagen del mapa. Abre el formulario, '
                    .'pulsa «Capturar mapa para la IA» sobre el mapa de la propiedad o sube una captura.'
                : 'No se generó el croquis: la propiedad no tiene ubicación marcada en el mapa y no se '
                    .'adjuntó ninguna captura. Marca el punto en la ficha y vuelve a generar.';
        }

        return 'No se generó el croquis: la captura del mapa no se pudo leer. Vuelve a capturarla o sube otra imagen.';
    }

    /**
     * The only source is the image that came with the request: the browser capture of the
     * Google embed, or a screenshot the advisor attached. Nothing is downloaded here.
     *
     * @return string[] data: URIs
     */
    private function collectReferenceImages(Property $property, array $options): array
    {
        if (empty($options['croquis_reference_path'])) {
            return [];
        }

        try {
            return [$this->encoder->fromDisk('local', $options['croquis_reference_path'], 512)];
        } catch (\Throwable $e) {
            Log::warning('No se pudo leer la imagen de referencia del croquis', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
