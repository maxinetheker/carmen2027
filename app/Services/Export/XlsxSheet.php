<?php

namespace App\Services\Export;

/**
 * Una hoja del libro. Las celdas se escriben como cadenas en línea (`inlineStr`)
 * en lugar de tabla de textos compartidos: el archivo pesa un poco más pero el
 * generador cabe en unas pocas decenas de líneas y no hay estado que sincronizar.
 */
class XlsxSheet
{
    public array $rows = [];

    /** @var array<int, float> anchos de columna, en caracteres */
    public array $widths = [];

    public function __construct(public string $name)
    {
    }

    /**
     * @param  array<int, mixed>  $cells  texto/número, o ['v' => …, 's' => 'head'|'blue'|…]
     */
    public function row(array $cells = []): static
    {
        $this->rows[] = $cells;

        return $this;
    }

    public function widths(array $widths): static
    {
        $this->widths = $widths;

        return $this;
    }

    public function xml(): string
    {
        $cols = '';
        foreach ($this->widths as $index => $width) {
            $cols .= '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$width.'" customWidth="1"/>';
        }

        $body = '';
        foreach ($this->rows as $number => $cells) {
            $body .= '<row r="'.($number + 1).'">';
            foreach ($cells as $column => $cell) {
                $body .= $this->cell(self::column($column).($number + 1), $cell);
            }
            $body .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .($cols ? "<cols>{$cols}</cols>" : '')
            ."<sheetData>{$body}</sheetData></worksheet>";
    }

    private function cell(string $reference, mixed $cell): string
    {
        $value = is_array($cell) ? ($cell['v'] ?? '') : $cell;
        $style = is_array($cell) ? ($cell['s'] ?? 'body') : 'body';
        $index = XlsxStyles::INDEX[$style] ?? XlsxStyles::band($style);
        $attributes = ' r="'.$reference.'" s="'.$index.'"';

        if ($value === null || $value === '') {
            return '<c'.$attributes.'/>';
        }
        if (is_int($value) || is_float($value)) {
            return '<c'.$attributes.'><v>'.$value.'</v></c>';
        }

        return '<c'.$attributes.' t="inlineStr"><is><t xml:space="preserve">'
            .self::escape((string) $value).'</t></is></c>';
    }

    public static function column(int $index): string
    {
        $name = '';
        for ($number = $index + 1; $number > 0; $number = intdiv($number - 1, 26)) {
            $name = chr(65 + ($number - 1) % 26).$name;
        }

        return $name;
    }

    private static function escape(string $value): string
    {
        // Excel rechaza el archivo entero si aparece un carácter de control.
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? $value;

        return htmlspecialchars($clean, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
