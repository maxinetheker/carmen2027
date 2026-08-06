<?php

namespace App\Http\Controllers\Admin;

use App\Models\Property;
use App\Models\PropertyFeature;
use App\Rules\YoutubeUrl;
use App\Services\PropertyContentManager;
use App\Support\RichTextSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PropertyController extends CrudController
{
    protected string $model = Property::class;
    protected string $route = 'properties';
    protected string $label = 'Propiedad';
    protected string $labelPlural = 'Propiedades';
    protected array $search = ['title', 'code', 'district'];
    protected array $columns = [
        'code' => 'Código', 'title' => 'Propiedad', 'district' => 'Distrito',
        'operation' => 'Operación', 'price' => 'Precio', 'status' => 'Estado',
    ];

    public function __construct(
        private PropertyContentManager $content,
        private RichTextSanitizer $sanitizer,
    ) {
    }

    protected function fields(): array
    {
        return [];
    }

    protected function rules(?int $id = null): array
    {
        $mediaSize = function (string $attribute, mixed $file, \Closure $fail): void {
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
            'image_url' => [
                'nullable', 'string', 'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! Str::startsWith((string) $value, '/')
                        && ! filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail('Usa una URL completa o una ruta interna que empiece con /.');
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:50000'],
            'featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'show_in_hero' => ['nullable', 'boolean'],
            'priority' => ['required', 'integer', 'between:0,999'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,avif', 'max:15360', $mediaSize],
            'media_files' => ['nullable', 'array', 'max:16'],
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

    protected function prepare(array $data, Request $request): array
    {
        $property = Arr::only($data, [
            'title', 'code', 'district', 'type', 'operation', 'status',
            'price', 'currency', 'area', 'bedrooms', 'bathrooms',
            'address', 'latitude', 'longitude', 'image_url', 'description', 'priority',
        ]);
        $property['description'] = $this->sanitizer->clean($data['description'] ?? null);
        $property['featured'] = $request->boolean('featured');
        $property['is_published'] = $request->boolean('is_published');
        $property['show_in_hero'] = $request->boolean('show_in_hero');
        $property['slug'] = Str::slug($property['title'].'-'.$property['code']);

        return $property;
    }

    protected function form(Model $record)
    {
        if ($record->exists) {
            $record->load(['media', 'features', 'youtubeVideos', 'documents', 'presentations']);
        }

        return view('admin.properties.form', [
            'record' => $record,
            'route' => $this->route,
            'label' => $this->label,
            'icons' => PropertyFeature::ICONS,
            'featurePresets' => PropertyFeature::PRESETS,
        ]);
    }

    protected function afterSave(Model $record, Request $request): void
    {
        $this->content->sync($record, $request);
    }

    protected function beforeDelete(Model $record): void
    {
        $this->content->purge($record);
    }
}
