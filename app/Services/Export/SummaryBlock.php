<?php

namespace App\Services\Export;

use Illuminate\Support\Collection;

/**
 * Un bloque de color del resumen: la franja con el título, la fila de cabeceras y
 * las filas de datos. Se repite cinco veces en la hoja, así que vive aparte.
 */
class SummaryBlock
{
    public function __construct(private XlsxSheet $sheet, private int $columns)
    {
    }

    /**
     * @param  string  $band  color de la franja: blue, green, amber, peach o grey
     * @param  Collection<int, array>  $rows
     */
    public function write(string $band, string $title, array $headings, Collection $rows): void
    {
        $this->sheet->row(array_map(
            fn (int $index) => ['v' => $index === 0 ? mb_strtoupper($title) : '', 's' => $band],
            range(0, $this->columns - 1)
        ));
        $this->sheet->row(array_map(fn ($heading) => ['v' => $heading, 's' => 'head'], $headings));

        if ($rows->isEmpty()) {
            $this->sheet->row([['v' => 'Sin registros en este periodo.', 's' => 'muted']]);
        }
        foreach ($rows as $row) {
            $this->sheet->row(array_map(
                fn ($value) => ['v' => $value, 's' => 'body'], array_values($row)
            ));
        }
        $this->sheet->row([]);
    }
}
