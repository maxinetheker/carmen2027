<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Property;
use App\Services\Import\PropertyImporter;
use App\Services\Import\PropertyPageFetcher;
use App\Services\Import\RemaxPropertyParser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class PropertyImportController extends Controller
{
    public function __construct(
        private PropertyPageFetcher $fetcher,
        private RemaxPropertyParser $parser,
        private PropertyImporter $importer,
    ) {
    }

    /** Lee la ficha del portal y devuelve lo encontrado para que se revise. */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'url' => ['nullable', 'url', 'max:500'],
            'html' => ['nullable', 'string', 'max:6000000'],
        ]);

        $html = trim((string) ($data['html'] ?? ''));
        $url = $data['url'] ?? null;

        if ($html === '') {
            if (! $url) {
                return response()->json(['message' => 'Pega el enlace de la propiedad.'], 422);
            }
            if (! $this->fetcher->supports($url)) {
                return response()->json(['message' => 'Por ahora solo se pueden importar fichas de remax.pe.'], 422);
            }
            try {
                $html = $this->fetcher->fetch($url);
            } catch (RuntimeException $exception) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }
        }

        $parsed = $this->parser->parse($html, $url);
        if (! $parsed['title'] || ! $parsed['price']) {
            return response()->json([
                'message' => 'No se reconoció el contenido de la página. Revisa que sea la ficha '
                    .'de una propiedad y no la lista de resultados.',
            ], 422);
        }

        $parsed['duplicate'] = $this->duplicate($parsed);

        return response()->json(['data' => $parsed]);
    }

    /** Crea la propiedad con los campos y fotos que se confirmaron. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'district' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Property::TYPES))],
            'operation' => ['required', Rule::in(['venta', 'alquiler'])],
            'currency' => ['required', Rule::in(['USD', 'PEN'])],
            'price' => ['required', 'numeric', 'min:0'],
            'area' => ['required', 'numeric', 'min:1'],
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:99'],
            'bathrooms' => ['nullable', 'numeric', 'min:0', 'max:99'],
            'address' => ['nullable', 'string', 'max:200'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:50000'],
            'features' => ['nullable', 'array', 'max:50'],
            'features.*.icon' => ['nullable', 'string', 'max:40'],
            'features.*.label' => ['required', 'string', 'max:80'],
            'features.*.value' => ['required', 'string', 'max:160'],
            'images' => ['nullable', 'array', 'max:30'],
            'images.*' => ['url', 'max:1000'],
            'source_url' => ['nullable', 'url', 'max:500'],
        ]);

        $result = $this->importer->create($data, $data['images'] ?? []);
        $property = $result['property'];

        Activity::create([
            'user_id' => auth()->id(),
            'subject_type' => Property::class, 'subject_id' => $property->id,
            'type' => 'imported',
            'description' => 'Propiedad importada desde '.($data['source_url'] ?? 'una ficha externa'),
            'happened_at' => now(),
        ]);

        return response()->json([
            'redirect' => route('admin.properties.edit', $property),
            'message' => "Propiedad {$property->code} importada con {$result['images']} foto(s)."
                .($result['failed'] ? " {$result['failed']} no se pudieron descargar." : '')
                .' Queda sin publicar para que revises los datos antes de sacarla al sitio.',
        ], 201);
    }

    /** Aviso, no bloqueo: a veces se reimporta a propósito para actualizar fotos. */
    private function duplicate(array $parsed): ?array
    {
        $existing = Property::where('title', $parsed['title'])
            ->orWhere(fn ($query) => $query->where('district', $parsed['district'])
                ->where('price', $parsed['price']))
            ->first();

        return $existing ? ['id' => $existing->id, 'code' => $existing->code,
            'title' => $existing->title, 'url' => route('admin.properties.edit', $existing)] : null;
    }
}
