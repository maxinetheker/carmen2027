<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PropertyRules;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Services\PropertyContentManager;
use App\Support\RichTextSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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

    /**
     * Land on the edit screen instead of the index: the gallery only switches to
     * one-request-per-file uploads once the property has an id, so this is where an
     * advisor can add an unlimited number of photos right after creating it.
     */
    public function store(Request $request)
    {
        $record = Property::create($this->prepare($request->validate($this->rules()), $request));
        $this->afterSave($record, $request);
        $this->log($record, 'created', "{$this->label} creado");

        return redirect()->route("admin.{$this->route}.edit", $record)
            ->with('success', "Propiedad {$record->code} creada. Ya puedes agregar todas las fotos y videos que necesites.");
    }

    protected function rules(?int $id = null): array
    {
        return PropertyRules::all();
    }

    protected function prepare(array $data, Request $request): array
    {
        $property = Arr::only($data, [
            'title', 'district', 'type', 'operation', 'status',
            'price', 'currency', 'area', 'bedrooms', 'bathrooms',
            'address', 'latitude', 'longitude', 'image_url', 'description', 'priority',
        ]);
        $property['description'] = $this->sanitizer->clean($data['description'] ?? null);
        $property['featured'] = $request->boolean('featured');
        $property['is_published'] = $request->boolean('is_published');
        $property['show_in_hero'] = $request->boolean('show_in_hero');
        $property['code'] = $this->codeFor($request);
        $property['slug'] = Str::slug($property['title'].'-'.$property['code']);

        return $property;
    }

    /** Existing properties keep their code; new ones get the next free correlative. */
    private function codeFor(Request $request): string
    {
        $id = $request->route('record');

        return ($id ? Property::whereKey($id)->value('code') : null) ?? Property::nextCode();
    }

    protected function form(Model $record)
    {
        if ($record->exists) {
            $record->load(['media', 'features', 'youtubeVideos', 'documents', 'presentations']);
        } else {
            $record->code = Property::nextCode();
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
