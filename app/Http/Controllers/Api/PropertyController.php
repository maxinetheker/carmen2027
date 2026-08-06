<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Rules\YoutubeUrl;
use App\Services\PropertyFeatureManager;
use App\Services\PropertyVideoManager;
use App\Support\RichTextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PropertyController extends CrudController
{
    protected string $model = Property::class;
    protected string $resourceClass = PropertyResource::class;
    protected string $label = 'Propiedad';
    protected array $search = ['title', 'code', 'district'];
    protected array $with = ['media', 'features', 'youtubeVideos'];

    public function __construct(
        private RichTextSanitizer $sanitizer,
        private PropertyVideoManager $videos,
        private PropertyFeatureManager $features,
    ) {
    }

    protected function rules(?int $id = null): array
    {
        return [
            'title' => ['required', 'max:160'],
            'code' => ['required', 'max:30', Rule::unique('properties')->ignore($id)],
            'district' => ['required', 'max:100'],
            'type' => ['required', Rule::in(['departamento', 'casa', 'oficina', 'terreno'])],
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
            'description' => ['nullable', 'string', 'max:50000'],
            'image_url' => [
                'nullable', 'string', 'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! Str::startsWith((string) $value, '/')
                        && ! filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail('Usa una URL completa o una ruta interna que empiece con /.');
                    }
                },
            ],
            'featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'show_in_hero' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'between:0,999'],
            'features' => ['nullable', 'array', 'max:50'],
            'features.*.icon' => ['nullable', Rule::in(array_keys(PropertyFeature::ICONS))],
            'features.*.label' => ['nullable', 'string', 'max:80'],
            'features.*.value' => ['nullable', 'string', 'max:160'],
            'youtube_videos' => ['nullable', 'array', 'max:10'],
            'youtube_videos.*.url' => ['nullable', 'string', 'max:500', new YoutubeUrl],
            'youtube_videos.*.original_url' => ['nullable', 'string', 'max:500', new YoutubeUrl],
            'youtube_videos.*.title' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function prepare(array $data, Request $request): array
    {
        $data['description'] = $this->sanitizer->clean($data['description'] ?? null);
        $data['featured'] = $request->boolean('featured');
        $data['is_published'] = $request->has('is_published') ? $request->boolean('is_published') : true;
        $data['show_in_hero'] = $request->boolean('show_in_hero');
        $data['priority'] = $data['priority'] ?? 0;
        $data['slug'] = Str::slug($data['title'].'-'.$data['code']);
        unset($data['features'], $data['youtube_videos']);

        return $data;
    }

    protected function afterSave(\Illuminate\Database\Eloquent\Model $record, Request $request): void
    {
        $this->features->replace($record, $request->input('features', []));
        $this->videos->replace($record, $request->input('youtube_videos', []));
    }

    protected function beforeDelete(\Illuminate\Database\Eloquent\Model $record): void
    {
        Storage::disk('public')->deleteDirectory("properties/{$record->id}");
    }

}
