<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePropertyPresentationJob;
use App\Models\Property;
use App\Models\PropertyPresentation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PropertyPresentationController extends Controller
{
    public function panel(Property $property)
    {
        $property->load(['media', 'documents', 'presentations']);

        return view('admin.properties.presentation-panel', ['record' => $property]);
    }

    public function store(Property $property, Request $request)
    {
        $templateKeys = array_keys(config('brochure_templates.templates'));
        $logoKeys = array_keys(config('brochure_templates.logos'));
        $images = config('brochure_templates.max_images');
        $pages = config('brochure_templates.max_pages');

        $data = $request->validate([
            'template_key' => ['required', Rule::in($templateKeys)],
            'logo_mode' => ['required', Rule::in(['auto', 'manual', 'off'])],
            'logo_key' => ['required_if:logo_mode,manual', 'nullable', Rule::in($logoKeys)],
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
            'croquis_mode' => ['required', Rule::in(['auto', 'off'])],
            'croquis_reference' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
            'extra_prompt' => ['nullable', 'string', 'max:2000'],
        ]);

        $validMediaIds = $property->media()->where('type', 'image')->pluck('id')->all();
        $selectedIds = array_values(array_intersect(
            array_map('intval', $data['selected_image_ids'] ?? []),
            $validMediaIds
        ));
        $coverId = in_array((int) ($data['cover_media_id'] ?? 0), $validMediaIds, true)
            ? (int) $data['cover_media_id']
            : null;

        if ($data['images_mode'] === 'manual' && ! in_array($coverId, $selectedIds, true)) {
            return response()->json([
                'message' => 'Selecciona exactamente una imagen principal entre las imágenes elegidas.',
                'errors' => ['cover_media_id' => ['La imagen principal debe formar parte de la selección.']],
            ], 422);
        }

        $croquisReferencePath = $data['croquis_mode'] === 'auto' && $request->hasFile('croquis_reference')
            ? $request->file('croquis_reference')->store("properties/{$property->id}/tmp", 'local')
            : null;

        $options = [
            'template_key' => $data['template_key'],
            'logo_mode' => $data['logo_mode'],
            'logo_key' => $data['logo_key'] ?? null,
            'images_mode' => $data['images_mode'],
            'image_count' => $data['images_mode'] === 'manual' ? count($selectedIds) : null,
            'selected_image_ids' => $selectedIds,
            'cover_media_id' => $coverId,
            'interest_mode' => $data['interest_mode'],
            'interest_manual' => $data['interest_manual'] ?? null,
            'faq_mode' => $data['faq_mode'],
            'faq_manual' => $data['faq_manual'] ?? [],
            'title_mode' => $data['title_mode'],
            'title_manual' => $data['title_manual'] ?? null,
            'max_pages' => $data['max_pages'],
            'audience' => $data['audience'],
            'croquis_mode' => $data['croquis_mode'],
            'croquis_reference_path' => $croquisReferencePath,
            'extra_prompt' => $data['extra_prompt'] ?? null,
        ];

        $presentation = $property->presentations()->create([
            'created_by' => $request->user()->id,
            'status' => 'queued',
            'template_key' => $data['template_key'],
            'options' => $options,
        ]);

        GeneratePropertyPresentationJob::dispatch($presentation->id);

        return response()->json(['id' => $presentation->id, 'status' => $presentation->status]);
    }

    public function status(Property $property, PropertyPresentation $presentation)
    {
        abort_unless($presentation->property_id === $property->id, 404);

        return response()->json([
            'status' => $presentation->status,
            'status_label' => $presentation->status_label,
            'pdf_url' => $presentation->pdf_url,
            'page_count' => $presentation->page_count,
            'error_message' => $presentation->error_message,
        ]);
    }

    public function destroy(Property $property, PropertyPresentation $presentation)
    {
        abort_unless($presentation->property_id === $property->id, 404);

        if ($presentation->pdf_path) {
            Storage::disk($presentation->pdf_disk)->delete($presentation->pdf_path);
        }
        $presentation->delete();

        return back()->with('success', 'Presentación eliminada.');
    }
}
