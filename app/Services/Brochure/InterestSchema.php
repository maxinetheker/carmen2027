<?php

namespace App\Services\Brochure;

/**
 * JSON schema for the "why buy now" AI response, kept beside InterestResearcher so both
 * files stay within this project's 150-line convention.
 */
class InterestSchema
{
    public static function definition(): array
    {
        return [
            'name' => 'brochure_interest',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'hook' => ['type' => ['string', 'null']],
                    'hook_source' => ['type' => ['string', 'null']],
                    'cards' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                            ],
                            'required' => ['title', 'description'],
                            'additionalProperties' => false,
                        ],
                    ],
                    'quote' => ['type' => ['string', 'null']],
                    // Restates the advisor's own description, so unlike the market claims
                    // below it needs no external source to survive.
                    'summary_paragraph' => ['type' => ['string', 'null']],
                    'trust_paragraph' => ['type' => ['string', 'null']],
                    'trust_sources' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'stats' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'value' => ['type' => 'string'],
                                'label' => ['type' => 'string'],
                                'source' => ['type' => ['string', 'null']],
                            ],
                            'required' => ['value', 'label', 'source'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => [
                    'hook', 'hook_source', 'cards', 'quote', 'summary_paragraph',
                    'trust_paragraph', 'trust_sources', 'stats',
                ],
                'additionalProperties' => false,
            ],
        ];
    }
}
