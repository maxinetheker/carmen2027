<?php

namespace App\Http\Requests;

use App\Models\Property;
use App\Services\Ai\OpenAiImageClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Validation for the "generate social image" modal. Deliberately a shorter list than the
 * brochure's: no template, no page limit and no free-form prompt — a post is one picture,
 * so the only shape choices are the format and the quality.
 */
class SocialImageRules
{
    public static function all(Property $property, Request $request): array
    {
        $images = config('brochure_templates.max_images');

        return [
            'format' => ['required', Rule::in(array_keys(OpenAiImageClient::SIZES))],
            'quality' => ['required', Rule::in(array_keys(OpenAiImageClient::QUALITIES))],
            'logo_mode' => ['required', Rule::in(['auto', 'manual', 'off'])],
            'logo_key' => ['required_if:logo_mode,manual', 'nullable', Rule::in(array_keys(config('brochure_templates.logos')))],
            'images_mode' => ['required', Rule::in(['auto', 'manual'])],
            'selected_image_ids' => ['required_if:images_mode,manual', 'array', "max:{$images['max']}"],
            'selected_image_ids.*' => ['integer', 'distinct'],
            'cover_media_id' => ['required_if:images_mode,manual', 'nullable', 'integer'],
            'interest_mode' => ['required', Rule::in(['auto', 'manual', 'off'])],
            'interest_manual' => ['required_if:interest_mode,manual', 'nullable', 'string', 'max:4000'],
            'title_mode' => ['required', Rule::in(['auto', 'manual', 'off'])],
            'title_manual' => ['required_if:title_mode,manual', 'nullable', 'string', 'max:160'],
            'audience' => ['required', Rule::in(['personas', 'empresas'])],
            'include_agent' => ['nullable', 'boolean'],
            'agent_pose' => ['nullable', 'string', 'max:200'],
            'croquis_mode' => [
                'required', Rule::in(['auto', 'off']),
                static fn (string $attribute, mixed $value, Closure $fail) => self::croquisIsPossible(
                    $property, (string) $value, $fail
                ),
            ],
        ];
    }

    /**
     * The poster's locator map is rendered from the property's coordinates, so without
     * them there is nothing to draw — said now rather than after the image is paid for.
     */
    private static function croquisIsPossible(Property $property, string $mode, Closure $fail): void
    {
        if ($mode !== 'auto' || ($property->latitude && $property->longitude)) {
            return;
        }

        $fail('Esta propiedad no tiene ubicación marcada, así que no se puede incluir el croquis. '
            .'Márcala en la ficha o cambia el croquis a «Desactivado».');
    }
}
