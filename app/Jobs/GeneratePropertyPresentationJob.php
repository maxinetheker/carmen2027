<?php

namespace App\Jobs;

use App\Models\PropertyPresentation;
use App\Services\Brochure\CroquisGenerator;
use App\Services\Brochure\FaqGenerator;
use App\Services\Brochure\ImageSelector;
use App\Services\Brochure\InterestResearcher;
use App\Services\Brochure\LogoSelector;
use App\Services\Brochure\PresentationRenderer;
use App\Services\Brochure\TitleGenerator;
use App\Services\PropertyDocumentManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Unlike the CRM reminder jobs (dispatched synchronously via Bus::dispatchSync — fine
 * for a single email), this one chains several OpenAI calls plus PDF rendering and can
 * easily run 10-60s+. It genuinely needs the queue (ShouldQueue) so the admin request
 * returns immediately and the panel polls for completion instead of risking a PHP
 * request timeout on shared hosting.
 */
class GeneratePropertyPresentationJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public int $presentationId) {}

    public function handle(
        PropertyDocumentManager $documents,
        ImageSelector $images,
        LogoSelector $logos,
        TitleGenerator $titles,
        FaqGenerator $faqs,
        InterestResearcher $interest,
        CroquisGenerator $croquis,
        PresentationRenderer $renderer,
    ): void {
        $presentation = PropertyPresentation::findOrFail($this->presentationId);
        $presentation->update(['status' => 'processing']);

        try {
            $property = $presentation->property()
                ->with(['media', 'features', 'documents'])->firstOrFail();
            $options = $presentation->options;
            $documentContext = $documents->contextFor($property);
            $theme = config("brochure_templates.templates.{$options['template_key']}");

            $usage = ['input_tokens' => 0, 'output_tokens' => 0, 'cached_tokens' => 0];
            $addUsage = function (array $call) use (&$usage) {
                $usage['input_tokens'] += $call['input_tokens'] ?? 0;
                $usage['output_tokens'] += $call['output_tokens'] ?? 0;
                $usage['cached_tokens'] += $call['cached_tokens'] ?? 0;
            };

            $imageResult = $images->select($property, $options);
            $addUsage($imageResult['usage']);

            $logoResult = $logos->select($options, $theme);
            $addUsage($logoResult['usage']);

            $titleResult = $titles->generate($property, $options, $documentContext);
            $addUsage($titleResult['usage']);

            $faqResult = $faqs->generate($property, $options, $documentContext);
            $addUsage($faqResult['usage']);

            $interestResult = $interest->research($property, $options, $documentContext);
            $addUsage($interestResult['usage']);

            $croquisResult = $croquis->generate($property, $options, $theme['accent']);
            $addUsage($croquisResult['usage']);

            $generated = [
                'title' => $titleResult['title'],
                'faqs' => $faqResult['faqs'],
                'interest' => $interestResult['content'],
                'croquis_svg' => $croquisResult['svg'],
                'media_ids' => $imageResult['media_ids'],
                'cover_media_id' => $imageResult['cover_media_id'],
                'logo_key' => $logoResult['key'],
            ];

            $rendered = $renderer->render($property, $options, $generated);

            $path = "properties/{$property->id}/presentations/".Str::uuid().'.pdf';
            Storage::disk('public')->put($path, $rendered['pdf']);

            $presentation->update([
                'status' => 'done',
                'pdf_disk' => 'public',
                'pdf_path' => $path,
                'page_count' => $rendered['page_count'],
                'ai_content' => $generated,
                'input_tokens' => $usage['input_tokens'],
                'output_tokens' => $usage['output_tokens'],
                'cached_tokens' => $usage['cached_tokens'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Falló la generación de una presentación de propiedad', [
                'property_presentation_id' => $presentation->id,
                'error' => $e->getMessage(),
            ]);
            $presentation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
