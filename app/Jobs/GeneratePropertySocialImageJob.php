<?php

namespace App\Jobs;

use App\Models\PropertySocialImage;
use App\Services\Ai\OpenAiImageClient;
use App\Services\Brochure\ImageSelector;
use App\Services\Brochure\InterestResearcher;
use App\Services\Brochure\LogoSelector;
use App\Services\Brochure\OsmStaticMap;
use App\Services\Brochure\TitleGenerator;
use App\Services\PropertyDocumentManager;
use App\Services\Social\SocialPromptBuilder;
use App\Services\Social\SocialReferenceCollector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds one social-media poster. Reuses the brochure's text generators so a post says
 * the same things the PDF says, then hands the copy plus the real photos to the image
 * model. Queued like the presentation job: image generation alone can take minutes.
 */
class GeneratePropertySocialImageJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $socialImageId) {}

    public function handle(
        PropertyDocumentManager $documents,
        ImageSelector $images,
        LogoSelector $logos,
        TitleGenerator $titles,
        InterestResearcher $interest,
        OsmStaticMap $staticMap,
        SocialReferenceCollector $referenceCollector,
        SocialPromptBuilder $promptBuilder,
        OpenAiImageClient $imageClient,
    ): void {
        $record = PropertySocialImage::findOrFail($this->socialImageId);
        $record->update(['status' => 'processing']);

        try {
            $property = $record->property()->with(['media', 'features', 'documents'])->firstOrFail();
            $options = $record->options;
            $theme = config('brochure_templates.templates.'.config('brochure_templates.default_template'));
            $documentContext = $documents->contextFor($property);

            $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'cached_tokens' => 0];
            $addUsage = function (array $call) use (&$usage) {
                foreach (['input_tokens', 'output_tokens', 'cached_tokens'] as $key) {
                    $usage[$key] += $call[$key] ?? 0;
                }
            };

            $imageResult = $images->select($property, $options);
            $addUsage($imageResult['usage']);
            $logoResult = $logos->select($options, $theme);
            $addUsage($logoResult['usage']);
            $titleResult = $titles->generate($property, $options, $documentContext);
            $addUsage($titleResult['usage']);
            $interestResult = $interest->research($property, $options, $documentContext);
            $addUsage($interestResult['usage']);

            $generated = [
                'title' => $titleResult['title'],
                'cards' => $interestResult['content']['cards'] ?? [],
                'media_ids' => $imageResult['media_ids'],
                'cover_media_id' => $imageResult['cover_media_id'],
                'logo_key' => $logoResult['key'],
                // The poster gets the plain map render rather than the brochure's SVG
                // croquis: the image model needs raster input, and the map is already one.
                'croquis_png' => $this->croquis($property, $options, $staticMap),
            ];

            $references = $referenceCollector->collect($property, $options, $generated);
            $prompt = $promptBuilder->build($property, $options, $generated, $references);

            $result = $imageClient->generate($prompt, $options['format'], $options['quality'], $references);
            $addUsage($result['usage']);

            $path = "properties/{$property->id}/social/".Str::uuid().'.png';
            Storage::disk('public')->put($path, $result['bytes']);

            unset($generated['croquis_png']);
            $record->update([
                'status' => 'done',
                'image_disk' => 'public',
                'image_path' => $path,
                'ai_content' => $generated + ['prompt' => $prompt, 'warnings' => $this->warnings($options, $references)],
                'input_tokens' => $usage['input_tokens'],
                'output_tokens' => $usage['output_tokens'],
                'cached_tokens' => $usage['cached_tokens'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Falló la generación de una imagen social', [
                'property_social_image_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
            $record->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    private function croquis($property, array $options, OsmStaticMap $staticMap): ?string
    {
        if (($options['croquis_mode'] ?? 'off') !== 'auto'
            || ! $property->latitude || ! $property->longitude) {
            return null;
        }

        return $staticMap->png((float) $property->latitude, (float) $property->longitude);
    }

    /** @return string[] */
    private function warnings(array $options, array $references): array
    {
        $warnings = [];
        if (! $references) {
            $warnings[] = 'La propiedad no tenía fotos utilizables, así que la pieza se generó sin imágenes reales del inmueble.';
        }
        if (($options['croquis_mode'] ?? 'off') === 'auto'
            && ! collect($references)->contains(fn ($r) => $r['name'] === 'croquis.png')) {
            $warnings[] = 'No se pudo incluir el croquis: la propiedad no tiene ubicación marcada en el mapa.';
        }

        return $warnings;
    }
}
