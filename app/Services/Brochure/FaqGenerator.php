<?php

namespace App\Services\Brochure;

use App\Models\Property;
use App\Services\Ai\AiSettings;
use App\Services\Ai\OpenAiClient;

class FaqGenerator
{
    public function __construct(private OpenAiClient $ai, private AiSettings $aiSettings) {}

    /**
     * @return array{faqs:array<int,array{question:string,answer:string}>,usage:array}
     */
    public function generate(Property $property, array $options, string $documentContext): array
    {
        $emptyUsage = ['input_tokens' => 0, 'output_tokens' => 0, 'cached_tokens' => 0];
        $mode = $options['faq_mode'] ?? 'off';

        if ($mode === 'off') {
            return ['faqs' => [], 'usage' => $emptyUsage];
        }

        if ($mode === 'manual') {
            $faqs = collect($options['faq_manual'] ?? [])
                ->map(fn ($row) => [
                    'question' => trim((string) ($row['question'] ?? '')),
                    'answer' => trim((string) ($row['answer'] ?? '')),
                ])
                ->filter(fn ($row) => $row['question'] !== '' && $row['answer'] !== '')
                ->values()
                ->all();

            return ['faqs' => $faqs, 'usage' => $emptyUsage];
        }

        $count = (int) ($options['max_pages'] ?? 3) >= 3 ? 8 : 5;

        $prompt = PromptContext::withDocuments(PromptContext::propertySummary($property), $documentContext)
            ."\n\n".PromptContext::audienceFraming($options['audience'] ?? 'personas')
            ."\n\nEscribe {$count} preguntas frecuentes que un comprador/inquilino real haría sobre esta "
            .'propiedad, con sus respuestas. Basa las respuestas solo en los datos de arriba. Si una pregunta '
            .'típica (por ejemplo sobre situación registral, deudas o gastos) no tiene respaldo suficiente en '
            .'los datos, respóndela de forma genérica invitando a confirmar el detalle con la asesora, sin '
            .'inventar el dato específico. Completa las preguntas pedidas en vez de devolver menos.';

        $schema = [
            'name' => 'brochure_faq',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'faqs' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'question' => ['type' => 'string'],
                                'answer' => ['type' => 'string'],
                            ],
                            'required' => ['question', 'answer'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['faqs'],
                'additionalProperties' => false,
            ],
        ];

        $instructions = PromptContext::instructions($this->aiSettings->basePrompt(), $options['extra_prompt'] ?? null);
        $result = $this->ai->text($prompt, $schema, false, $instructions);
        $faqs = collect($result['data']['faqs'] ?? [])
            ->filter(fn ($row) => trim((string) ($row['question'] ?? '')) !== '' && trim((string) ($row['answer'] ?? '')) !== '')
            ->take($count)
            ->values()
            ->all();

        return ['faqs' => $faqs, 'usage' => $result['usage'] ?? $emptyUsage];
    }
}
