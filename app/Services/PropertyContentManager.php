<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyFeature;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyContentManager
{
    public function __construct(
        private ImageOptimizer $optimizer,
        private PropertyVideoManager $videos,
    )
    {
    }

    public function sync(Property $property, Request $request): void
    {
        $this->removeMedia($property, $request->input('remove_media', []));
        $cover = $request->file('cover_image');
        $coverMedia = $cover instanceof UploadedFile
            ? $this->storeImage($property, $cover) : null;
        $newMedia = [];
        foreach ($request->file('media_files', []) as $file) {
            if (! $file instanceof UploadedFile) continue;
            $newMedia[] = str_starts_with((string) $file->getMimeType(), 'image/')
                ? $this->storeImage($property, $file)
                : $this->storeVideo($property, $file);
        }
        $manifest = json_decode((string) $request->input('media_manifest', '[]'), true);
        $this->reorder(
            $property, is_array($manifest) ? $manifest : [],
            $newMedia, $coverMedia?->id
        );
        $this->chooseCover(
            $property, $request->integer('cover_media_id'), $coverMedia?->id
        );
        $this->replaceFeatures($property, $request->input('features', []));
        $this->videos->replace($property, $request->input('youtube_videos', []));
    }

    public function purge(Property $property): void
    {
        Storage::disk('public')->deleteDirectory("properties/{$property->id}");
    }

    public function storeMedia(Property $property, UploadedFile $file)
    {
        return str_starts_with((string) $file->getMimeType(), 'image/')
            ? $this->storeImage($property, $file)
            : $this->storeVideo($property, $file);
    }

    private function removeMedia(Property $property, array $ids): void
    {
        $property->media()->whereIn('id', $ids)->get()->each(function ($media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        });
    }

    private function storeImage(Property $property, UploadedFile $file)
    {
        $optimized = $this->optimizer->store($file, $property->id);
        return $property->media()->create($optimized + [
            'type' => 'image', 'disk' => 'public',
            'original_name' => $file->getClientOriginalName(),
            'sort_order' => ((int) $property->media()->max('sort_order')) + 1,
        ]);
    }

    private function storeVideo(Property $property, UploadedFile $file)
    {
        $extension = match ($file->getMimeType()) {
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            default => 'mp4',
        };
        $path = $file->storeAs(
            "properties/{$property->id}/videos", Str::uuid().'.'.$extension, 'public'
        );
        return $property->media()->create([
            'type' => 'video', 'disk' => 'public', 'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(),
            'sort_order' => ((int) $property->media()->max('sort_order')) + 1,
        ]);
    }

    private function reorder(Property $property, array $manifest, array $newMedia, ?int $coverId): void
    {
        $ordered = $coverId ? [$coverId] : [];
        foreach ($manifest as $token) {
            if (preg_match('/^existing:(\d+)$/', (string) $token, $match)) {
                $media = $property->media()->whereKey((int) $match[1])->first();
            } elseif (preg_match('/^new:(\d+)$/', (string) $token, $match)) {
                $media = $newMedia[(int) $match[1]] ?? null;
            } else {
                $media = null;
            }
            if ($media && ! in_array($media->id, $ordered, true)) {
                $ordered[] = $media->id;
            }
        }
        $remaining = $property->media()->whereNotIn('id', $ordered)->pluck('id')->all();
        foreach (array_merge($ordered, $remaining) as $order => $id) {
            $property->media()->whereKey($id)->update(['sort_order' => $order]);
        }
    }

    private function chooseCover(Property $property, int $requested, ?int $uploaded): void
    {
        $cover = $uploaded ? $property->media()->whereKey($uploaded)->first() : null;
        $cover ??= $requested
            ? $property->media()->whereKey($requested)->where('type', 'image')->first()
            : null;
        $cover ??= $property->media()->where('type', 'image')
            ->orderByDesc('is_cover')->orderBy('sort_order')->first();
        $property->media()->update(['is_cover' => false]);
        $cover?->update(['is_cover' => true]);
    }

    private function replaceFeatures(Property $property, array $features): void
    {
        $property->features()->delete();
        foreach ($features as $index => $feature) {
            $label = trim((string) ($feature['label'] ?? ''));
            $value = trim((string) ($feature['value'] ?? ''));
            if ($label === '' || $value === '') continue;
            $icon = array_key_exists($feature['icon'] ?? '', PropertyFeature::ICONS)
                ? $feature['icon'] : 'info';
            $property->features()->create(compact('icon', 'label', 'value') + [
                'sort_order' => $index,
            ]);
        }
    }
}
