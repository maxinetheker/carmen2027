<?php

namespace App\Support;

use Illuminate\Support\Str;

final class LegacyPropertyMapper
{
    private const TYPES = [8 => 'terreno', 9 => 'terreno', 16 => 'departamento', 17 => 'casa', 18 => 'terreno', 19 => 'casa', 20 => 'casa'];
    private const DISTRICTS = [8 => 'Chilca', 9 => 'Los Olivos', 16 => 'Miraflores', 17 => 'Ancón', 18 => 'Chimbote', 19 => 'Jesús María', 20 => 'Ventanilla'];
    private const PRIORITIES = [16 => 100, 19 => 90, 20 => 80, 17 => 70, 8 => 60, 9 => 50, 18 => 40];

    public function __construct(private readonly RichTextSanitizer $sanitizer) {}

    public function attributes(array $row, array $detail): array
    {
        $legacyId = (int) $row['id'];
        [$latitude, $longitude] = $this->coordinates($row['google_maps'] ?? null);
        $title = $this->title((string) $row['nombre']);
        $hero = in_array($legacyId, [16, 19, 20], true);

        return [
            'title' => $title,
            'slug' => Str::slug($title.' CM '.str_pad((string) $legacyId, 3, '0', STR_PAD_LEFT)),
            'code' => 'CM-'.str_pad((string) $legacyId, 3, '0', STR_PAD_LEFT),
            'type' => self::TYPES[$legacyId] ?? 'propiedad',
            'operation' => Str::contains(Str::lower((string) $row['tipo']), 'alquil') ? 'alquiler' : 'venta',
            'district' => $this->text($row['distrito'] ?? null) ?: self::DISTRICTS[$legacyId],
            'address' => $this->text($row['direccion'] ?? null) ?: $this->text($row['ubicacion'] ?? null),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'price' => (float) $row['precio'],
            'currency' => 'USD',
            'bedrooms' => max(0, (int) ($detail['dormitorio'] ?? 0)),
            'bathrooms' => max(0, (float) ($detail['banos'] ?? 0)),
            'area' => max(0, (float) ($detail['area_m2'] ?? 0)),
            'status' => 'available',
            'featured' => $hero,
            'description' => $this->sanitizer->clean((string) ($row['descripcion'] ?? '')),
            'image_url' => null,
            'is_published' => (bool) $row['active'],
            'show_in_hero' => $hero,
            'priority' => self::PRIORITIES[$legacyId] ?? 0,
            'created_at' => $this->text($row['created_at'] ?? null) ?: now(),
            'updated_at' => $this->text($row['updated_at'] ?? null) ?: now(),
        ];
    }

    private function coordinates(mixed $value): array
    {
        $parts = array_map('trim', explode(',', (string) $value));
        return count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])
            ? [(float) $parts[0], (float) $parts[1]] : [null, null];
    }

    private function title(string $title): string
    {
        $title = trim(preg_replace('/\s+/u', ' ', $title) ?: $title);
        $title = mb_convert_case(mb_strtolower($title, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        foreach (['De', 'Del', 'La', 'Las', 'Los', 'En', 'A', 'Al', 'Y', 'Como', 'Para'] as $word) {
            $title = str_replace(" {$word} ", ' '.mb_strtolower($word, 'UTF-8').' ', $title);
        }

        $title = str_replace(
            [' Duplex ', ' Mas ', ' Clinica ', ' Expansion ', ' la Chutana', ' los Olivos'],
            [' Dúplex ', ' más ', ' Clínica ', ' Expansión ', ' La Chutana', ' Los Olivos'],
            $title
        );
        $title = preg_replace('/(\d)\s*M(?:2|²)/u', '$1 m²', $title) ?: $title;
        $title = preg_replace('/\s+([,.;:])/u', '$1', $title) ?: $title;

        return $title;
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
