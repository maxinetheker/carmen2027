<?php

use App\Models\Property;
use App\Support\LegacySqlDump;
use App\Support\RichTextSanitizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sanitizer = app(RichTextSanitizer::class);
        $rows = LegacySqlDump::rows(
            database_path('legacy/table_propiedades.sql'), 'table_propiedades'
        );

        foreach ($rows as $row) {
            $code = 'CM-'.str_pad((string) $row['id'], 3, '0', STR_PAD_LEFT);
            $property = Property::where('code', $code)->first();
            $restored = $sanitizer->clean((string) ($row['descripcion'] ?? ''));
            if (! $property || ! $restored || ! $this->sameVisibleText($property->description, $restored)) {
                continue;
            }

            DB::table('properties')->where('id', $property->id)->update([
                'description' => $restored,
            ]);
        }
    }

    public function down(): void
    {
        // La reparación conserva el contenido original y no debe volver a degradarlo.
    }

    private function sameVisibleText(?string $current, string $original): bool
    {
        return $this->comparable($current) === $this->comparable($original);
    }

    private function comparable(?string $html): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (class_exists(Normalizer::class)) {
            $text = Normalizer::normalize($text, Normalizer::FORM_KC) ?: $text;
        }
        $text = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $text) ?: $text;
        $text = str_replace(["\u{200D}", "\u{2063}", "\u{FE0F}"], '', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?: $text);
    }
};
