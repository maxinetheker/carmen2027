<?php

namespace App\Services\Brochure;

use App\Models\Property;

/**
 * Builds only pages that have enough material to earn their place. Every page has
 * a fixed A4 box and footer; PagePlanner supplies the upper bound for this run.
 */
class PageAssembler
{
    public function __construct(
        private BrochureImageFitter $fitter,
        private PropertyFacts $facts,
        private PageContentLimiter $content,
        private SvgSanitizer $sanitizer,
    ) {}

    public function assemble(Property $property, array $options, array $generated, array $theme): array
    {
        $agent = $this->facts->agent();
        $maxPages = min(
            (int) config('brochure_templates.max_pages.max'),
            max(1, (int) ($options['max_pages'] ?? config('brochure_templates.max_pages.default')))
        );
        $plannedPages = min($maxPages, max(1, (int) ($generated['page_count'] ?? $maxPages)));
        $media = $property->media->where('type', 'image')->keyBy('id');
        $mediaIds = array_values(array_filter($generated['media_ids'] ?? [], fn ($id) => $media->has($id)));
        $coverId = $generated['cover_media_id'] ?? ($mediaIds[0] ?? null);
        $galleryIds = array_values(array_diff($mediaIds, [$coverId]));
        $galleryItems = collect($galleryIds)->map(fn ($id) => $media->get($id))->filter()->values();
        $interest = $generated['interest'] ?? [];
        $templateKey = $options['template_key'];
        $logo = $this->logoImage($generated['logo_key'] ?? null);
        $title = $this->content->shortText($generated['title'] ?? $property->title, 80) ?: $property->title;

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
                'hook' => $this->content->shortText($interest['hook'] ?? null, 130),
                'cards' => $this->content->cards($interest['cards'] ?? []),
                'quote' => $this->content->shortText($interest['quote'] ?? null, 120),
            ],
        ]];

        $contentPages = [];
        $highlightItems = $galleryItems->take(4)->values()->all();
        $hasRichHighlights = ! empty($interest['trust_paragraph']) || ! empty($interest['stats']);
        // Prefer the AI's rewrite of the description: the raw field is often pasted from
        // a listing in SHOUTING CAPS with a phone number glued to the end, which read as
        // if nothing had been edited at all.
        $propertySummary = $hasRichHighlights ? null : $this->content->shortText(
            $interest['summary_paragraph'] ?? strip_tags((string) $property->description), 240
        );
        if ($highlightItems || ! empty($interest)) {
            $contentPages[] = [
                'view' => 'brochures.pages.highlights',
                'data' => [
                    'theme' => $theme, 'agent' => $agent, 'logo' => $logo, 'ref' => $property->code,
                    'heading' => 'Conozca <span>'.e($property->district).'</span> más de cerca',
                    'gallery' => $this->fitter->rowOf($highlightItems, $hasRichHighlights ? 45 : 60),
                    'specs' => array_slice($this->facts->specs($property), 0, 4),
                    'trustParagraph' => $this->content->htmlExcerpt($interest['trust_paragraph'] ?? null, 320),
                    'stats' => $this->content->stats(array_slice($interest['stats'] ?? [], 0, 4)),
                    'steps' => $this->facts->steps(),
                    'propertySummary' => $propertySummary,
                    'layoutClass' => $hasRichHighlights ? 'highlights-rich' : 'highlights-balanced',
                ],
            ];
        }

        $croquisSvg = $generated['croquis_svg'] ?? null;
        // Limits sit above what the prompts ask for, so a well-behaved answer arrives
        // whole and only a runaway one is trimmed.
        $faqs = $this->content->faqs($generated['faqs'] ?? [], $croquisSvg ? 3 : 5, 165);
        $description = $this->content->shortText(
            $interest['summary_paragraph'] ?? strip_tags((string) $property->description), 240
        );
        if ($croquisSvg || $faqs || $description) {
            $planoItem = $galleryItems->get(4) ?? $galleryItems->first();
            $contentPages[] = [
                'view' => 'brochures.pages.details',
                'data' => [
                    'theme' => $theme, 'agent' => $agent, 'logo' => $logo, 'ref' => $property->code,
                    'heading' => 'Lo que necesita saber <span>antes de decidir</span>',
                    'croquisImage' => $this->sanitizer->dataUri($croquisSvg),
                    'planoImage' => $croquisSvg && $planoItem
                        ? $this->fitter->fitMm($planoItem->disk, $planoItem->path, 67, 62)
                        : null,
                    'faqs' => $faqs,
                    'ficha' => $this->content->facts(
                        $croquisSvg ? array_slice($this->facts->fichaTecnica($property), 0, 4) : $this->facts->fichaTecnica($property),
                        95
                    ),
                    'description' => $description,
                ],
            ];
        }

        foreach (array_chunk($galleryItems->slice(4)->all(), 6) as $photos) {
            if (count($photos) < 3) {
                continue;
            }
            $imageHeight = count($photos) > 4 ? 58 : 83;
            $contentPages[] = [
                'view' => 'brochures.pages.gallery',
                'data' => [
                    'theme' => $theme, 'agent' => $agent, 'logo' => $logo, 'ref' => $property->code,
                    'heading' => 'Recorra cada <span>detalle</span>',
                    'rows' => $this->fitter->rowsOfTwo($photos, $imageHeight),
                    'imageHeight' => $imageHeight,
                ],
            ];
        }

        return ['title' => $title, 'pages' => array_slice(array_merge($pages, $contentPages), 0, $plannedPages)];
    }

    public function logoImage(?string $key): ?string
    {
        $logos = config('brochure_templates.logos');
        if (! $key || ! isset($logos[$key])) {
            return null;
        }

        $path = storage_path(config('brochure_templates.logos_path').'/'.$logos[$key]['file']);

        return is_file($path) ? $this->fitter->fitContainMm(file_get_contents($path), 34, 18) : null;
    }

}
