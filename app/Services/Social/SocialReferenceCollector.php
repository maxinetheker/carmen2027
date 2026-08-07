<?php

namespace App\Services\Social;

use App\Models\Property;
use App\Services\Brochure\PageAssembler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Gathers the real pictures the poster must be built from: the property photo, the
 * RE/MAX logo, the agent's portrait and the croquis. Sending these to /images/edits is
 * what stops the model inventing a different house, a different logo and a stranger's
 * face. Order matters — SocialPromptBuilder labels each slot by position.
 */
class SocialReferenceCollector
{
    private const AGENT_PHOTO = 'images/carmen-mestanza.png';

    public function __construct(private PageAssembler $assembler) {}

    /**
     * @return array<int,array{name:string,bytes:string,mime:string,role:string}>
     */
    public function collect(Property $property, array $options, array $generated): array
    {
        $references = [];

        foreach ($this->propertyPhotos($property, $generated) as $index => $bytes) {
            $references[] = [
                'name' => "propiedad-{$index}.jpg",
                'bytes' => $bytes,
                'mime' => 'image/jpeg',
                'role' => $index === 0
                    ? 'Foto principal del inmueble. Debe ocupar la mayor parte de la pieza como fondo o bloque protagonista.'
                    : 'Foto secundaria del inmueble; úsala pequeña o descártala si no aporta.',
            ];
        }

        if ($logo = $this->logo($generated['logo_key'] ?? null)) {
            $references[] = [
                'name' => 'logo.png', 'bytes' => $logo, 'mime' => 'image/png',
                'role' => 'Logotipo de la agencia. Colócalo pequeño en una esquina, sin deformarlo ni cambiar sus colores.',
            ];
        }

        if (! empty($options['include_agent']) && $agent = $this->agentPhoto()) {
            $references[] = [
                'name' => 'agente.png', 'bytes' => $agent, 'mime' => 'image/png',
                'role' => 'Retrato de la asesora. Recórtala y colócala '
                    .trim((string) ($options['agent_pose'] ?? 'de pie a un lado, mirando a cámara'))
                    .'. Conserva su rostro y su ropa exactamente como en la foto.',
            ];
        }

        if (! empty($generated['croquis_png'])) {
            $references[] = [
                'name' => 'croquis.png', 'bytes' => $generated['croquis_png'], 'mime' => 'image/png',
                'role' => 'Croquis de ubicación. Insértalo como recuadro pequeño, legible, en una zona libre.',
            ];
        }

        return $references;
    }

    /** @return array<int,string> raw JPEG bytes, cover first */
    private function propertyPhotos(Property $property, array $generated): array
    {
        $media = $property->media->where('type', 'image')->keyBy('id');
        $ids = array_values(array_filter($generated['media_ids'] ?? [], fn ($id) => $media->has($id)));
        $coverId = $generated['cover_media_id'] ?? ($ids[0] ?? null);
        $ordered = array_values(array_unique(array_filter(array_merge([$coverId], $ids))));

        $photos = [];
        foreach (array_slice($ordered, 0, 2) as $id) {
            $item = $media->get($id);
            try {
                $photos[] = Storage::disk($item->disk)->get($item->path);
            } catch (\Throwable $e) {
                Log::warning('No se pudo leer una foto para la imagen social', ['error' => $e->getMessage()]);
            }
        }

        return $photos;
    }

    private function logo(?string $key): ?string
    {
        $logos = config('brochure_templates.logos');
        if (! $key || ! isset($logos[$key])) {
            return null;
        }
        $path = storage_path(config('brochure_templates.logos_path').'/'.$logos[$key]['file']);

        return is_file($path) ? file_get_contents($path) : null;
    }

    private function agentPhoto(): ?string
    {
        $path = public_path(self::AGENT_PHOTO);

        return is_file($path) ? file_get_contents($path) : null;
    }
}
