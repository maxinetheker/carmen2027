<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyMedia;
use App\Services\PropertyContentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * One upload per request, for the admin gallery.
 *
 * The property form used to post every file in a single multipart submit, which capped
 * a gallery at whatever PHP's max_file_uploads / post_max_size allowed (and silently
 * dropped the rest). Uploading one file at a time removes that ceiling entirely and
 * lets the browser report exactly which file failed, instead of failing the whole save.
 */
class PropertyMediaController extends Controller
{
    private const IMAGE_LIMIT = 15 * 1024 * 1024;

    public function __construct(private PropertyContentManager $content) {}

    public function store(Request $request, Property $property)
    {
        $request->validate([
            'file' => [
                'required', 'file',
                'mimetypes:image/jpeg,image/png,image/webp,image/avif,video/mp4,video/webm,video/quicktime',
                'max:204800',
            ],
        ], [
            'file.mimetypes' => 'Formato no admitido. Usa JPG, PNG, WebP, AVIF, MP4, WebM o MOV.',
            'file.max' => 'El archivo supera el máximo de 200 MB.',
            'file.required' => 'No se recibió el archivo. Puede que supere el límite del servidor.',
        ]);

        $file = $request->file('file');
        $isImage = str_starts_with((string) $file->getMimeType(), 'image/');

        if ($isImage && $file->getSize() > self::IMAGE_LIMIT) {
            throw ValidationException::withMessages([
                'file' => 'Cada imagen debe pesar como máximo 15 MB.',
            ]);
        }
        if ($isImage) {
            $dimensions = @getimagesize($file->getRealPath());
            if ($dimensions && $dimensions[0] * $dimensions[1] > 50_000_000) {
                throw ValidationException::withMessages([
                    'file' => 'La imagen tiene dimensiones demasiado grandes.',
                ]);
            }
        }

        $media = $this->content->storeMedia($property, $file);
        if ($media->type === 'image' && ! $property->media()->where('is_cover', true)->exists()) {
            $media->update(['is_cover' => true]);
        }

        return response()->json([
            'id' => $media->id,
            'type' => $media->type,
            'url' => $media->url,
            'name' => $media->original_name,
            'is_cover' => (bool) $media->fresh()->is_cover,
        ], 201);
    }

    public function destroy(Property $property, PropertyMedia $media)
    {
        abort_unless($media->property_id === $property->id, 404);

        $wasCover = $media->is_cover;
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        if ($wasCover) {
            $property->media()->where('type', 'image')->orderBy('sort_order')->first()
                ?->update(['is_cover' => true]);
        }

        return response()->json(['message' => 'Archivo eliminado.']);
    }
}
