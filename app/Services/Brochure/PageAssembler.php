<?php

namespace App\Services\Brochure;

use App\Models\Property;

/**
 * Builds the ordered list of Blade page views + data for a presentation: cover is
 * always first, then the highlights and details pages only when there's actual
 * content for them and the chosen max-pages allows it.
 */
class PageAssembler
{
    public function __construct(
        private BrochureImageFitter $fitter,
        private PropertyFacts $facts,
    ) {}

    public function assemble(Property $property, array $options, array $generated, array $theme): array
    {
        $agent = $this->facts->agent();
        $maxPages = max(1, min((int) ($options['max_pages'] ?? 3), 3));

        $media = $property->media->where('type', 'image')->keyBy('id');
        $mediaIds = array_values(array_filter($generated['media_ids'] ?? [], fn ($id) => $media->has($id)));
        $coverId = $generated['cover_media_id'] ?? ($mediaIds[0] ?? null);
        $galleryIds = array_values(array_diff($mediaIds, [$coverId]));

        $title = $generated['title'] ?? $property->title;
        $interest = $generated['interest'] ?? null;
        $templateKey = $options['template_key'];
        $logo = $this->logoImage($options);

        $pages = [[
            'view' => "brochures.templates.{$templateKey}.cover",
            'data' => [
                'theme' => $theme, 'agent' => $agent, 'ref' => $property->code, 'logo' => $logo,
                'title' => e($title),
                'titleSize' => TextFit::size($title, $this->facts->titleTiers($theme['title_size'])),
                'subtitle' => $this->facts->subtitle($property),
                'badge' => $property->operation === 'alquiler' ? 'SE ALQUILA' : 'SE VENDE',
                'heroImage' => $coverId
                    ? $this->fitter->fitMm($media[$coverId]->disk, $media[$coverId]->path, $theme['hero_box']['w'], $theme['hero_box']['h'])
                    : asset('images/property-1.jpg'),
                'priceMain' => $this->facts->priceMain($property),
                'priceSub' => $this->facts->priceSub($property),
                'hook' => $interest['hook'] ?? null,
                'cards' => $interest['cards'] ?? [],
                'quote' => $interest['quote'] ?? null,
            ],
        ]];

        // Gallery figures (in selection order), split evenly across the ~182mm content width.
        $galleryItems = collect($galleryIds)->map(fn ($id) => $media->get($id))->filter()->take(4)->values()->all();
        $galleryWidth = 182 / max(1, count($galleryItems));
        $galleryFigures = array_map(fn ($item) => [
            'src' => $this->fitter->fitMm($item->disk, $item->path, $galleryWidth, 45),
        ], $galleryItems);

        $hasHighlights = $galleryFigures || ($interest['trust_paragraph'] ?? null) || ($interest['stats'] ?? []);
        if ($maxPages >= 2 && $hasHighlights) {
            $pages[] = [
                'view' => 'brochures.pages.highlights',
                'data' => [
                    'theme' => $theme, 'agent' => $agent, 'logo' => $logo,
                    'heading' => 'Conozca <span>'.e($property->district).'</span> más de cerca',
                    'gallery' => $galleryFigures,
                    'trustParagraph' => $interest['trust_paragraph'] ?? null,
                    'stats' => $interest['stats'] ?? [],
                    'steps' => $this->facts->steps(),
                ],
            ];
        }

        $faqs = $generated['faqs'] ?? [];
        $croquisSvg = $generated['croquis_svg'] ?? null;
        $planoItem = $galleryItems[0] ?? null;
        if ($maxPages >= 3 && ($croquisSvg || $faqs)) {
            $pages[] = [
                'view' => 'brochures.pages.details',
                'data' => [
                    'theme' => $theme, 'agent' => $agent, 'logo' => $logo,
                    'heading' => 'Lo que necesita saber <span>antes de decidir</span>',
                    'croquisSvg' => $croquisSvg,
                    'planoImage' => $croquisSvg && $planoItem
                        ? $this->fitter->fitMm($planoItem->disk, $planoItem->path, 67, 62)
                        : null,
                    'faqs' => $faqs,
                ],
            ];
        }

        return ['title' => $title, 'pages' => $pages];
    }

    /**
     * "Automático" always resolves to the plain symbol (no letters) — it already
     * reads fine on both light and dark themes, so no per-template logic is needed.
     */
    public function logoImage(array $options): ?string
    {
        $mode = $options['logo_mode'] ?? 'auto';
        if ($mode === 'off') {
            return null;
        }

        $logos = config('brochure_templates.logos');
        $key = $mode === 'manual' && isset($logos[$options['logo_key'] ?? null])
            ? $options['logo_key']
            : config('brochure_templates.default_logo');

        $path = storage_path(config('brochure_templates.logos_path').'/'.$logos[$key]['file']);
        if (! is_file($path)) {
            return null;
        }

        return $this->fitter->fitContainMm(file_get_contents($path), 34, 18);
    }
}
