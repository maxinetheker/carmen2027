<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyMediaResource;
use App\Models\Activity;
use App\Models\Property;
use App\Services\PropertyContentManager;
use App\Services\PropertyMediaReorderer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PropertyMediaController extends Controller
{
    public function __construct(
        private PropertyContentManager $content,
        private PropertyMediaReorderer $reorderer,
    ) {
    }

    public function storePhoto(Request $request, int $property)
    {
        $record = Property::findOrFail($property);
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,avif', 'max:15360'],
        ]);

        return new PropertyMediaResource($this->persist($record, $request->file('photo')));
    }

    public function store(Request $request, int $property)
    {
        $record = Property::findOrFail($property);
        $request->validate([
            'media' => [
                'required', 'file',
                'mimetypes:image/jpeg,image/png,image/webp,image/avif,video/mp4,video/webm,video/quicktime',
                'max:204800',
            ],
        ]);
        $file = $request->file('media');
        if (str_starts_with((string) $file->getMimeType(), 'image/')
            && $file->getSize() > 15 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'media' => 'Cada imagen debe pesar como máximo 15 MB.',
            ]);
        }

        return new PropertyMediaResource($this->persist($record, $file));
    }

    public function destroy(int $property, int $media)
    {
        $record = Property::findOrFail($property);
        $item = $record->media()->whereKey($media)->firstOrFail();
        $wasCover = $item->is_cover;
        Storage::disk($item->disk)->delete($item->path);
        $item->delete();
        if ($wasCover) {
            $record->media()->where('type', 'image')->orderBy('sort_order')->first()
                ?->update(['is_cover' => true]);
        }
        $this->logUpdate($record);

        return response()->json(['message' => 'Archivo eliminado.']);
    }

    public function reorder(Request $request, int $property)
    {
        $record = Property::findOrFail($property);
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
            'cover_media_id' => ['nullable', 'integer'],
        ]);
        $media = $this->reorderer->apply(
            $record, $validated['order'], $validated['cover_media_id'] ?? null
        );
        $this->logUpdate($record);

        return PropertyMediaResource::collection($media);
    }

    private function persist(Property $property, UploadedFile $file)
    {
        $media = $this->content->storeMedia($property, $file);
        if ($media->type === 'image' && ! $property->media()->where('is_cover', true)->exists()) {
            $media->update(['is_cover' => true]);
        }
        $this->logUpdate($property);

        return $media->refresh();
    }

    private function logUpdate(Property $property): void
    {
        Activity::create([
            'user_id' => auth()->id(),
            'subject_type' => Property::class,
            'subject_id' => $property->id,
            'type' => 'updated',
            'description' => 'Propiedad actualizada',
            'happened_at' => now(),
        ]);
    }
}
