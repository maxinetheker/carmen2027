<?php

namespace App\Support;

use App\Models\Property;
use Illuminate\Support\Facades\DB;

final class LegacyPropertyImporter
{
    public const CODES = ['CM-008', 'CM-009', 'CM-016', 'CM-017', 'CM-018', 'CM-019', 'CM-020'];

    public function __construct(
        private readonly LegacyPropertyMapper $mapper,
        private readonly LegacyPropertyFeatures $features,
        private readonly LegacyPropertyMediaImporter $media,
    ) {}

    public function import(): void
    {
        $rows = LegacySqlDump::rows(database_path('legacy/table_propiedades.sql'), 'table_propiedades');
        $details = collect(LegacySqlDump::rows(
            database_path('legacy/propiedades_detalles.sql'), 'propiedades_detalles'
        ))->keyBy(fn (array $row) => (int) $row['id']);

        DB::transaction(function () use ($rows, $details): void {
            foreach ($rows as $row) {
                $detail = $details->get((int) $row['id'], []);
                $property = Property::create($this->mapper->attributes($row, $detail));
                $this->features->create($property, $detail);
                $this->media->import($property, $row);
            }
        });
    }
}
