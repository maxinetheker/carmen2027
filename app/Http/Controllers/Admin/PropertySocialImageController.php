<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocialImageRules;
use App\Jobs\GeneratePropertySocialImageJob;
use App\Models\Property;
use App\Models\PropertySocialImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertySocialImageController extends Controller
{
    public function panel(Property $property)
    {
        $property->load(['media', 'socialImages']);

        return view('admin.properties.social-panel', ['record' => $property]);
    }

    public function store(Property $property, Request $request)
    {
        $data = $request->validate(SocialImageRules::all($property, $request));

        $validMediaIds = $property->media()->where('type', 'image')->pluck('id')->all();
        $selectedIds = array_values(array_intersect(
            array_map('intval', $data['selected_image_ids'] ?? []), $validMediaIds
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

        $options = [
            'format' => $data['format'],
            'quality' => $data['quality'],
            'logo_mode' => $data['logo_mode'],
            'logo_key' => $data['logo_key'] ?? null,
            'images_mode' => $data['images_mode'],
            'image_count' => $data['images_mode'] === 'manual' ? count($selectedIds) : null,
            'selected_image_ids' => $selectedIds,
            'cover_media_id' => $coverId,
            'interest_mode' => $data['interest_mode'],
            'interest_manual' => $data['interest_manual'] ?? null,
            'title_mode' => $data['title_mode'],
            'title_manual' => $data['title_manual'] ?? null,
            'audience' => $data['audience'],
            'croquis_mode' => $data['croquis_mode'],
            'include_agent' => $request->boolean('include_agent'),
            'agent_pose' => $data['agent_pose'] ?? null,
            // The brochure options this modal deliberately does not offer.
            'max_pages' => 1,
        ];

        $image = $property->socialImages()->create([
            'created_by' => $request->user()->id,
            'status' => 'queued',
            'format' => $data['format'],
            'quality' => $data['quality'],
            'options' => $options,
        ]);

        GeneratePropertySocialImageJob::dispatch($image->id);

        return response()->json(['id' => $image->id, 'status' => $image->status]);
    }

    public function status(Property $property, PropertySocialImage $socialImage)
    {
        abort_unless($socialImage->property_id === $property->id, 404);

        return response()->json([
            'status' => $socialImage->status,
            'status_label' => $socialImage->status_label,
            'image_url' => $socialImage->image_url,
            'error_message' => $socialImage->error_message,
            'warnings' => $socialImage->warnings,
        ]);
    }

    public function destroy(Property $property, PropertySocialImage $socialImage)
    {
        abort_unless($socialImage->property_id === $property->id, 404);

        if ($socialImage->image_path) {
            Storage::disk($socialImage->image_disk)->delete($socialImage->image_path);
        }
        $socialImage->delete();

        return back()->with('success', 'Imagen eliminada.');
    }
}
