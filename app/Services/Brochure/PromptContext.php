<?php

namespace App\Services\Brochure;

use App\Models\Property;

class PromptContext
{
    public static function noInventionRule(): string
    {
        return <<<'TEXT'
Reglas estrictas: no inventes datos, cifras, direcciones, certificaciones ni situación legal que no
estén respaldados por (a) los datos de la propiedad que se te dan, (b) el texto de los documentos
adjuntos que se te da, o (c) resultados reales de tu búsqueda web con una fuente verificable. Si no
tienes evidencia suficiente para una afirmación (por ejemplo estado registral, deudas o permisos), no
la afirmes: usa una frase genérica invitando a confirmar el dato con la asesora en vez de adivinar.
Nunca presentes una suposición como si fuera un hecho comprobado. Toda cifra de mercado (precios por
m², porcentajes, comparativas de zona) debe venir de una fuente real de tu búsqueda web; si no
encuentras una fuente confiable, omite esa cifra en vez de inventarla.
TEXT;
    }

    public static function propertySummary(Property $property): string
    {
        $features = $property->features->map(
            fn ($f) => "{$f->label}: {$f->value}"
        )->implode('; ');

        $lines = [
            "Propiedad: {$property->title} (código {$property->code})",
            "Tipo: {$property->type_label} en {$property->operation_label}",
            "Distrito/zona: {$property->district}",
            $property->address ? "Dirección: {$property->address}" : null,
            "Precio: {$property->currency} ".number_format((float) $property->price, 0),
            "Área: {$property->area} m² · Dormitorios: {$property->bedrooms} · Baños: {$property->bathrooms_label}",
            $features !== '' ? "Características: {$features}" : null,
            $property->description ? 'Descripción actual: '.strip_tags((string) $property->description) : null,
        ];

        return implode("\n", array_filter($lines));
    }

    public static function withDocuments(string $propertySummary, string $documentContext): string
    {
        if (trim($documentContext) === '') {
            return $propertySummary."\n\n(No se adjuntaron documentos de referencia para esta propiedad.)";
        }

        return $propertySummary."\n\nDocumentos de referencia adjuntos por la asesora:\n{$documentContext}";
    }

    public static function audienceFraming(string $audience): string
    {
        return $audience === 'empresas'
            ? 'Audiencia objetivo: empresas e inversionistas. Enfoca el mensaje en retorno de inversión, '
                .'plusvalía, operatividad/logística y seguridad jurídica de la compra — no en un tono de "hogar familiar".'
            : 'Audiencia objetivo: personas y familias. Enfoca el mensaje en calidad de vida, ubicación y '
                .'beneficios para vivir o mudarse, con un tono cercano.';
    }

    /**
     * Combines the fixed no-invention rule with the admin's global base prompt
     * (Ajustes › Inteligencia artificial) and any instructions typed for this
     * specific generation, so both apply on top of the built-in brochure prompt.
     */
    public static function instructions(?string $basePrompt, ?string $extra): string
    {
        $parts = [self::noInventionRule()];
        if ($basePrompt = trim((string) $basePrompt)) {
            $parts[] = "Instrucciones generales de la marca: {$basePrompt}";
        }
        if ($extra = trim((string) $extra)) {
            $parts[] = "Instrucciones adicionales para esta propiedad: {$extra}";
        }

        return implode("\n\n", $parts);
    }
}
