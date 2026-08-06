<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;

class PropertyDocumentManager
{
    /**
     * Character cap per document so a huge PDF can't blow the AI prompt budget.
     */
    private const MAX_EXTRACTED_CHARS = 20000;

    public function store(Property $property, UploadedFile $file): PropertyDocument
    {
        $path = $file->storeAs(
            "properties/{$property->id}/documents", Str::uuid().'.'.$file->extension(), 'local'
        );

        $document = $property->documents()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'extraction_status' => 'pending',
        ]);

        $this->extract($document);

        return $document->refresh();
    }

    public function destroy(PropertyDocument $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }

    /**
     * Combined, truncated text of every successfully-extracted document for a property,
     * used as grounding context so the AI cites real facts instead of inventing them.
     */
    public function contextFor(Property $property): string
    {
        return $property->documents
            ->where('extraction_status', 'done')
            ->map(fn (PropertyDocument $document) => "### {$document->original_name}\n".$document->extracted_text)
            ->implode("\n\n");
    }

    private function extract(PropertyDocument $document): void
    {
        try {
            $absolutePath = Storage::disk($document->disk)->path($document->path);
            $text = match (true) {
                $document->mime_type === 'application/pdf' => (new PdfParser)->parseFile($absolutePath)->getText(),
                str_starts_with((string) $document->mime_type, 'text/') => file_get_contents($absolutePath),
                default => null,
            };

            if ($text === null) {
                $document->update(['extraction_status' => 'unsupported']);

                return;
            }

            $text = trim(preg_replace('/[ \t]+/', ' ', $text));
            $document->update([
                'extracted_text' => Str::limit($text, self::MAX_EXTRACTED_CHARS, ''),
                'extraction_status' => 'done',
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo extraer texto del documento de propiedad', [
                'property_document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
            $document->update(['extraction_status' => 'failed']);
        }
    }
}
