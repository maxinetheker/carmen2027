<?php

namespace App\Http\Requests;

use App\Models\Property;
use App\Models\PropertyFeature;
use App\Rules\YoutubeUrl;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validation rules for the property form, kept next to the controller instead of inside
 * it so both files stay within this project's 150-line convention.
 *
 * `code` is absent on purpose: it is assigned by Property::nextCode() and never read
 * from the request, so a posted value can neither change an existing code nor collide
 * with one. `media_files` is uncapped because the gallery uploads one file per request
 * once the property exists; this batch is only the fallback used while creating it.
 */
class PropertyRules
{
    public static function all(): array
    {
        $mediaSize = static function (string $attribute, mixed $file, Closure $fail): void {
            if (! $file instanceof UploadedFile
                || ! str_starts_with((string) $file->getMimeType(), 'image/')) return;
            if ($file->getSize() > 15 * 1024 * 1024) {
                $fail('Cada imagen debe pesar como máximo 15 MB.');
            }
            $dimensions = @getimagesize($file->getRealPath());
            if ($dimensions && $dimensions[0] * $dimensions[1] > 50_000_000) {
                $fail('La imagen tiene dimensiones demasiado grandes.');
            }
        };

        $internalOrAbsoluteUrl = static function (string $attribute, mixed $value, Closure $fail): void {
            if (! Str::startsWith((string) $value, '/') && ! filter_var($value, FILTER_VALIDATE_URL)) {
                $fail('Usa una URL completa o una ruta interna que empiece con /.');
            }
        };

        return [
            'title' => ['required', 'max:160'],
            'district' => ['required', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Property::TYPES))],
            'operation' => ['required', Rule::in(['venta', 'alquiler'])],
            'status' => ['required', Rule::in(['available', 'reserved', 'sold'])],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['USD', 'PEN'])],
            'area' => ['required', 'numeric', 'min:1'],
            'bedrooms' => ['required', 'integer', 'min:0'],
            'bathrooms' => ['required', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:200'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'image_url' => ['nullable', 'string', 'max:500', $internalOrAbsoluteUrl],
            'description' => ['nullable', 'string', 'max:50000'],
            'featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'show_in_hero' => ['nullable', 'boolean'],
            'priority' => ['required', 'integer', 'between:0,999'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,avif', 'max:15360', $mediaSize],
            'media_files' => ['nullable', 'array'],
            'media_files.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,image/avif,video/mp4,video/webm,video/quicktime', 'max:204800', $mediaSize],
            'media_manifest' => ['nullable', 'json', 'max:10000'],
            'remove_media' => ['nullable', 'array'],
            'remove_media.*' => ['integer'],
            'cover_media_id' => ['nullable', 'integer'],
            'features' => ['nullable', 'array', 'max:50'],
            'features.*.icon' => ['nullable', Rule::in(array_keys(PropertyFeature::ICONS))],
            'features.*.label' => ['nullable', 'string', 'max:80'],
            'features.*.value' => ['nullable', 'string', 'max:160'],
            'youtube_videos' => ['nullable', 'array', 'max:10'],
            'youtube_videos.*.url' => ['nullable', 'string', 'max:500', new YoutubeUrl],
            'youtube_videos.*.title' => ['nullable', 'string', 'max:120'],
        ];
    }
}
