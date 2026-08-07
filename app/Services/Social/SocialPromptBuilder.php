<?php

namespace App\Services\Social;

use App\Models\Property;
use App\Services\Brochure\PropertyFacts;

/**
 * Writes the instruction the image model works from.
 *
 * The copy itself (title, selling points) is produced beforehand by the same text
 * generators the brochure uses, so a post says exactly what the PDF says. This class
 * only describes the poster: what to draw, where, and in which order of importance.
 */
class SocialPromptBuilder
{
    private const LAYOUTS = [
        'cuadrado' => 'formato cuadrado 1:1 para feed de Instagram/Facebook',
        'vertical' => 'formato vertical 2:3 para historias y reels',
        'horizontal' => 'formato horizontal 3:2 para portada de Facebook y LinkedIn',
    ];

    public function __construct(private PropertyFacts $facts) {}

    public function build(Property $property, array $options, array $generated, array $references): string
    {
        $agent = $this->facts->agent();
        $layout = self::LAYOUTS[$options['format']] ?? self::LAYOUTS['cuadrado'];
        $title = $generated['title'] ?? $property->title;
        $price = $this->facts->priceMain($property).' · '.$this->facts->priceSub($property);

        $prompt = "Diseña una pieza publicitaria inmobiliaria profesional en {$layout}, "
            .'lista para publicar en redes sociales, con estética de agencia premium: composición '
            .'limpia, jerarquía visual clara y mucho contraste entre el texto y el fondo.'
            ."\n\nContenido obligatorio, escrito EXACTAMENTE así y sin faltas de ortografía:"
            ."\n- Titular: «{$title}»"
            ."\n- Precio destacado: «{$price}»"
            ."\n- Distrito: «{$property->district}»"
            ."\n- Etiqueta de operación: «".($property->operation === 'alquiler' ? 'SE ALQUILA' : 'SE VENDE').'»';

        if (! empty($agent['phone'])) {
            $prompt .= "\n- Teléfono de contacto: «{$agent['phone']}»";
        }

        $prompt .= $this->sellingPoints($generated);
        $prompt .= $this->referenceGuide($references);

        return $prompt.$this->rules();
    }

    private function sellingPoints(array $generated): string
    {
        $cards = array_slice($generated['cards'] ?? [], 0, 3);
        if (! $cards) {
            return '';
        }

        $lines = array_map(
            fn ($card) => '  · '.trim((string) ($card['title'] ?? '')),
            $cards
        );

        return "\n- Hasta 3 puntos fuertes, muy breves:\n".implode("\n", $lines);
    }

    /**
     * The reference pictures arrive in a fixed order, so the prompt can tell the model
     * what each one is for. Without this it treats them as vague style inspiration and
     * redraws the property, the logo and the agent as invented look-alikes.
     */
    private function referenceGuide(array $references): string
    {
        if (! $references) {
            return "\n\nNo hay fotos de referencia: usa un fondo abstracto elegante, sin inventar "
                .'fotografías realistas del inmueble.';
        }

        $guide = "\n\nImágenes de referencia adjuntas, EN ESTE ORDEN. Úsalas tal cual, sin recrearlas:";
        foreach ($references as $index => $reference) {
            $guide .= "\n".($index + 1).'. '.$reference['role'];
        }

        return $guide;
    }

    private function rules(): string
    {
        return "\n\nReglas estrictas:"
            ."\n- No inventes datos, precios, áreas ni servicios que no estén arriba."
            ."\n- Todo el texto en español, correctamente escrito y legible a tamaño pequeño."
            ."\n- No repitas el mismo texto dos veces en la pieza."
            ."\n- Deja margen de seguridad en los bordes: nada de texto pegado al filo."
            ."\n- No agregues marcas de agua, direcciones web falsas ni logotipos distintos al entregado.";
    }
}
