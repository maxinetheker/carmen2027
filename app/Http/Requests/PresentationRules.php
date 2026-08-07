<?php

namespace App\Http\Requests;

use App\Models\Property;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Validation rules for the "generate presentation" modal, kept beside the controller so
 * both files stay within this project's 150-line convention.
 */
class PresentationRules
{
    public static function all(Property $property, Request $request): array
    {
        $images = config('brochure_templates.max_images');
        $pages = config('brochure_templates.max_pages');

        return [
            'template_key' => ['required', Rule::in(array_keys(config('brochure_templates.templates')))],
            'logo_mode' => ['required', Rule::in(['auto', 'manual', 'off'])],
            'logo_key' => ['required_if:logo_mode,manual', 'nullable', Rule::in(array_keys(config('brochure_templates.logos')))],
            'images_mode' => ['required', Rule::in(['auto', 'manual'])],
            'selected_image_ids' => ['required_if:images_mode,manual', 'array', "max:{$images['max']}"],
            'selected_image_ids.*' => ['integer', 'distinct'],
            'cover_media_id' => ['required_if:images_mode,manual', 'nullable', 'integer'],
            'interest_mode' => ['required', Rule::in(['auto', 'manual', 'off'])],
            'interest_manual' => ['required_if:interest_mode,manual', 'nullable', 'string', 'max:4000'],
            'faq_mode' => ['required', Rule::in(['auto', 'manual', 'off'])],
            'faq_manual' => ['nullable', 'array'],
            'faq_manual.*.question' => ['nullable', 'string', 'max:200'],
            'faq_manual.*.answer' => ['nullable', 'string', 'max:1000'],
            'title_mode' => ['required', Rule::in(['auto', 'manual', 'off'])],
            'title_manual' => ['required_if:title_mode,manual', 'nullable', 'string', 'max:160'],
            'max_pages' => ['required', 'integer', "between:{$pages['min']},{$pages['max']}"],
            'audience' => ['required', Rule::in(['personas', 'empresas'])],
            'croquis_mode' => [
                'required', Rule::in(['auto', 'off']),
                static fn (string $attribute, mixed $value, Closure $fail) => self::croquisIsPossible(
                    $property, $request, (string) $value, $fail
                ),
            ],
            'croquis_reference' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
            'extra_prompt' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Coordinates are enough on their own — the server renders the map from them — so the
     * only unwinnable case is a property with no location and no attached capture. Saying
     * so during validation, while the advisor still has the form open, beats queueing a
     * job that cannot succeed and reporting it once the AI calls have been paid for.
     */
    private static function croquisIsPossible(
        Property $property, Request $request, string $mode, Closure $fail
    ): void {
        if ($mode !== 'auto'
            || $request->hasFile('croquis_reference')
            || ($property->latitude && $property->longitude)) {
            return;
        }

        $fail('Esta propiedad no tiene ubicación marcada, así que no se puede dibujar el croquis. '
            .'Márcala en «Ubicación en el mapa» dentro de la ficha, adjunta una captura aquí, '
            .'o cambia el croquis a «Desactivado».');
    }
}
