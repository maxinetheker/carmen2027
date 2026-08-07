<?php

namespace App\Services\Brochure;

use App\Models\Property;
use App\Services\Ai\AiSettings;
use App\Services\Ai\OpenAiClient;
use App\Support\RichTextSanitizer;

/**
 * Produces the "why buy now" persuasion content (hook stat, benefit cards, quote,
 * trust paragraph, stat tiles). Every market claim (hook + stats + trust paragraph)
 * must carry a source URL that actually appears among the web_search results —
 * anything else is dropped rather than shown, per the no-invention rule.
 */
class InterestResearcher
{
    public function __construct(
        private OpenAiClient $ai,
        private AiSettings $aiSettings,
        private RichTextSanitizer $html,
    ) {}

    /**
     * @return array{content:?array,usage:array}
     */
    public function research(Property $property, array $options, string $documentContext): array
    {
        $emptyUsage = ['input_tokens' => 0, 'output_tokens' => 0, 'cached_tokens' => 0];
        $mode = $options['interest_mode'] ?? 'off';

        if ($mode === 'off') {
            return ['content' => null, 'usage' => $emptyUsage];
        }

        if ($mode === 'manual') {
            $text = trim((string) ($options['interest_manual'] ?? ''));

            return [
                'content' => $text !== '' ? ['trust_paragraph' => $this->html->clean($text), 'trust_sources' => [], 'cards' => [], 'stats' => [], 'hook' => null, 'quote' => null] : null,
                'usage' => $emptyUsage,
            ];
        }

        $maxPages = (int) ($options['max_pages'] ?? 3);
        $cardCount = $maxPages >= 3 ? 4 : 3;
        $statCount = $maxPages >= 3 ? 6 : 4;

        $prompt = PromptContext::withDocuments(PromptContext::propertySummary($property), $documentContext)
            ."\n\n".PromptContext::audienceFraming($options['audience'] ?? 'personas')
            ."\n\nBusca en internet información real y actual sobre el mercado inmobiliario de esta zona/tipo "
            .'de propiedad (precios de referencia, demanda, crecimiento, comparativas) para construir '
            .'argumentos de venta convincentes. Devuelve: un "hook" (una frase corta con una cifra comparativa '
            ."llamativa y su fuente), hasta {$cardCount} tarjetas de beneficio (título + descripción corta, pueden "
            .'basarse en los datos de la propiedad sin necesitar fuente externa), una frase motivacional breve '
            .'(quote, no es un dato factual sino una frase inspiradora), un párrafo de confianza sobre por qué '
            ."esta zona (trust_paragraph) con sus fuentes, y hasta {$statCount} estadísticas cortas (valor + "
            .'etiqueta + fuente). Toda cifra de mercado debe tener una fuente real de tu búsqueda web; si no '
            .'encuentras una, no la incluyas. Prefiere completar la cantidad máxima de tarjetas y estadísticas '
            .'pedidas en vez de devolver pocas, siempre que encuentres respaldo real para cada una.';
        $prompt .= "\nEl campo trust_paragraph puede incluir HTML simple para dar énfasis (p, strong, em, ul, li y br), "
            .'sin CSS, scripts ni enlaces. Mantén ese bloque en un máximo de 700 caracteres visibles.'
            // Budgeted at the source: the brochure boxes are fixed, so a short complete
            // sentence beats a long one chopped mid-word.
            ."\nCada tarjeta: título de máximo 38 caracteres y descripción de máximo 135, siempre "
            .'una frase completa terminada en punto. La quote: máximo 110 caracteres.'
            // Not source-gated below, because it only restates what the advisor already
            // wrote — the raw description was reaching the PDF in SHOUTING CAPS.
            ."\nDevuelve además summary_paragraph: la descripción de la propiedad reescrita en 1-2 "
            .'frases claras y vendedoras (máximo 240 caracteres), en mayúsculas y minúsculas normales, '
            .'sin teléfonos ni datos de contacto, sin añadir ningún dato que no esté en la descripción.';

        $schema = InterestSchema::definition();

        $instructions = PromptContext::instructions($this->aiSettings->basePrompt(), $options['extra_prompt'] ?? null);
        $result = $this->ai->text($prompt, $schema, true, $instructions);
        $data = $result['data'] ?? [];
        $sources = $result['sources'] ?? [];

        $content = [
            'hook' => $this->sourced($data['hook'] ?? null, $data['hook_source'] ?? null, $sources),
            'cards' => array_slice(array_values($data['cards'] ?? []), 0, $cardCount),
            'quote' => $data['quote'] ?? null,
            // Deliberately not passed through sourced()/groundedParagraph(): it adds no
            // market claim, it only rewrites the advisor's own description.
            'summary_paragraph' => $data['summary_paragraph'] ?? null,
            'trust_paragraph' => $this->html->clean(
                $this->groundedParagraph($data['trust_paragraph'] ?? null, $data['trust_sources'] ?? [], $sources)
            ),
            'stats' => $this->groundedStats($data['stats'] ?? [], $sources),
            'sources' => $sources,
        ];

        return ['content' => $content, 'usage' => $result['usage'] ?? $emptyUsage];
    }
    private function sourced(?string $value, ?string $source, array $sources): ?string
    {
        if (! $value || ! $source || ! in_array($source, $sources, true)) {
            return null;
        }

        return $value;
    }

    private function groundedParagraph(?string $paragraph, array $claimedSources, array $sources): ?string
    {
        if (! $paragraph) {
            return null;
        }
        $confirmed = array_intersect($claimedSources, $sources);

        return $confirmed ? $paragraph : null;
    }

    private function groundedStats(array $stats, array $sources): array
    {
        return array_values(array_filter($stats, function ($stat) use ($sources) {
            $source = $stat['source'] ?? null;

            return $source && in_array($source, $sources, true);
        }));
    }
}
