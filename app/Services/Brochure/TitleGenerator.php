<?php

namespace App\Services\Brochure;

use App\Models\Property;
use App\Services\Ai\AiSettings;
use App\Services\Ai\OpenAiClient;

class TitleGenerator
{
    public function __construct(private OpenAiClient $ai, private AiSettings $aiSettings) {}

    /**
     * @return array{title:string,usage:array}
     */
    public function generate(Property $property, array $options, string $documentContext): array
    {
        $emptyUsage = ['input_tokens' => 0, 'output_tokens' => 0, 'cached_tokens' => 0];
        $mode = $options['title_mode'] ?? 'off';

        if ($mode === 'off') {
            return ['title' => $property->title, 'usage' => $emptyUsage];
        }

        if ($mode === 'manual') {
            $title = trim((string) ($options['title_manual'] ?? ''));

            return ['title' => $title !== '' ? $title : $property->title, 'usage' => $emptyUsage];
        }

        $prompt = PromptContext::withDocuments(PromptContext::propertySummary($property), $documentContext)
            ."\n\n".PromptContext::audienceFraming($options['audience'] ?? 'personas')
            ."\n\nEscribe un título llamativo de máximo 80 caracteres para la portada del brochure de esta "
            .'propiedad, en español, que resuma su atractivo principal (tipo, ubicación o cifra clave real). '
            .'Sin comillas, sin emojis, sin signos de exclamación excesivos.';

        $schema = [
            'name' => 'brochure_title',
            'schema' => [
                'type' => 'object',
                'properties' => ['title' => ['type' => 'string']],
                'required' => ['title'],
                'additionalProperties' => false,
            ],
        ];

        $instructions = PromptContext::instructions($this->aiSettings->basePrompt(), $options['extra_prompt'] ?? null);
        $result = $this->ai->text($prompt, $schema, false, $instructions);
        $title = trim((string) ($result['data']['title'] ?? ''));

        return [
            'title' => $title !== '' ? $title : $property->title,
            'usage' => $result['usage'] ?? $emptyUsage,
        ];
    }
}
