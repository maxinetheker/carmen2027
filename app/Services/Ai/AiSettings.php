<?php

namespace App\Services\Ai;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Crypt;

class AiSettings
{
    public const DEFAULT_MODEL = 'gpt-5.6-luna';

    public function apiKey(): ?string
    {
        $value = SiteSetting::where('key', 'ai_openai_api_key')->value('value');
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function hasApiKey(): bool
    {
        return (bool) SiteSetting::where('key', 'ai_openai_api_key')->value('value');
    }

    public function model(): string
    {
        return SiteSetting::where('key', 'ai_openai_model')->value('value') ?: self::DEFAULT_MODEL;
    }

    public function basePrompt(): ?string
    {
        return SiteSetting::where('key', 'ai_base_prompt')->value('value') ?: null;
    }

    public function storeApiKey(string $rawKey): void
    {
        SiteSetting::updateOrCreate(['key' => 'ai_openai_api_key'], [
            'value' => Crypt::encryptString($rawKey),
            'group' => 'ai',
            'type' => 'encrypted',
        ]);
    }
}
