<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PropertyMediaResource;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Services\ImageOptimizer;
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
    protected array $with = ['media'];

    public function __construct(
        private ImageOptimizer $optimizer,
        private RichTextSanitizer $sanitizer,
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
            'featured' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'show_in_hero' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'between:0,999'],
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

        return $data;
    }

    protected function beforeDelete(\Illuminate\Database\Eloquent\Model $record): void
    {
        Storage::disk('public')->deleteDirectory("properties/{$record->id}");
    }

    public function addPhoto(Request $request, int $property)
    {
        $property = Property::findOrFail($property);
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,avif', 'max:15360'],
        ]);

        $optimized = $this->optimizer->store($request->file('photo'), $property->id);
        $media = $property->media()->create($optimized + [
            'type' => 'image', 'disk' => 'public',
            'original_name' => $request->file('photo')->getClientOriginalName(),
            'sort_order' => ((int) $property->media()->max('sort_order')) + 1,
        ]);

        if (! $property->media()->where('is_cover', true)->exists()) {
            $media->update(['is_cover' => true]);
        }

        $this->log($property, 'updated', "{$this->label} actualizado");

        return new PropertyMediaResource($media);
    }

    public function removePhoto(int $property, int $media)
    {
        $property = Property::findOrFail($property);
        $item = $property->media()->whereKey($media)->firstOrFail();
        Storage::disk($item->disk)->delete($item->path);
        $item->delete();
        $this->log($property, 'updated', "{$this->label} actualizado");

        return response()->json(['message' => 'Foto eliminada.']);
    }
}
