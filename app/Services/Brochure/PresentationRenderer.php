<?php

namespace App\Services\Brochure;

use App\Models\Property;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Turns a property + AI-generated content into a PDF: PageAssembler decides which
 * pages apply, this class just renders that page list with dompdf.
 */
class PresentationRenderer
{
    public function __construct(
        private PageAssembler $assembler,
        private PropertyFacts $facts,
    ) {}

    /**
     * @return array{pdf:?string,html?:string,page_count:int}
     */
    public function render(Property $property, array $options, array $generated, bool $debugHtml = false): array
    {
        $templateKey = $options['template_key'] ?? config('brochure_templates.default_template');
        $theme = config("brochure_templates.templates.{$templateKey}");
        if (! $theme) {
            throw new \RuntimeException("Plantilla de brochure desconocida: {$templateKey}");
        }

        $assembled = $this->assembler->assemble($property, $options, $generated, $theme);

        $html = view('brochures.document', [
            'theme' => $theme,
            'documentTitle' => $assembled['title'],
            'pages' => $assembled['pages'],
        ])->render();

        if ($debugHtml) {
            return ['pdf' => null, 'html' => $html, 'page_count' => count($assembled['pages'])];
        }

        $expectedPages = count($assembled['pages']);
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $output = $pdf->output();
        $actualPages = $pdf->getDomPDF()->getCanvas()->get_page_count();

        if ($actualPages !== $expectedPages) {
            throw new \RuntimeException(
                "La plantilla produjo {$actualPages} hoja(s) para {$expectedPages} página(s) planificada(s)."
            );
        }

        return ['pdf' => $output, 'page_count' => $actualPages];
    }

    /**
     * A standalone HTML document for the template picker in the "generate" modal —
     * the real cover partial with placeholder content, not a separate mockup to
     * keep in sync, so the preview never drifts from what actually gets produced.
     */
    public function previewCoverHtml(string $templateKey): string
    {
        $theme = config("brochure_templates.templates.{$templateKey}");
        $title = 'Departamento moderno de 3 dormitorios con vista al parque';

        $pages = [[
            'view' => "brochures.templates.{$templateKey}.cover",
            'data' => [
                'theme' => $theme,
                'agent' => $this->facts->agent(),
                'logo' => $this->assembler->logoImage(config('brochure_templates.default_logo')),
                'ref' => 'CM-000',
                'title' => e($title),
                'titleSize' => TextFit::size($title, $this->facts->titleTiers($theme['title_size'])),
                'subtitle' => 'San Isidro · Av. Los Conquistadores 123',
                'badge' => 'SE VENDE',
                'heroImage' => asset('images/property-1.jpg'),
                'priceMain' => 'USD 185,000',
                'priceSub' => '120 m² · 3 dorm · 2 baños',
                'hook' => 'Zona con alta demanda: los departamentos de San Isidro se revalorizan cada año',
                'cards' => [
                    ['title' => 'Ubicación estratégica', 'description' => 'A pasos de parques, oficinas y colegios top de la zona.'],
                    ['title' => 'Buen estado', 'description' => 'Acabados modernos, lista para habitar sin obras pendientes.'],
                ],
                'quote' => null,
            ],
        ]];

        return view('brochures.document', [
            'theme' => $theme,
            'documentTitle' => 'Vista previa',
            'pages' => $pages,
        ])->render();
    }
}
