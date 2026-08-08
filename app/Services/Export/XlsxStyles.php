<?php

namespace App\Services\Export;

/**
 * Hoja de estilos del Excel. Son los mismos colores del resumen que ya se llevaba
 * a mano (azul para compradores, verde para visitas, ámbar para captaciones…),
 * para que el archivo generado se lea igual que el de siempre.
 */
class XlsxStyles
{
    /** Índice de cada estilo dentro de `cellXfs`, en el mismo orden que se escriben. */
    public const INDEX = [
        'default' => 0, 'title' => 1, 'section' => 2, 'head' => 3,
        'body' => 4, 'muted' => 5, 'number' => 6, 'strong' => 7,
    ];

    public const BANDS = ['blue' => 'FFB4C6E7', 'green' => 'FFC6E0B4', 'amber' => 'FFFFD966',
        'peach' => 'FFF8CBAD', 'grey' => 'FFD9D9D9'];

    public static function xml(): string
    {
        $bandFills = '';
        foreach (self::BANDS as $color) {
            $bandFills .= '<fill><patternFill patternType="solid"><fgColor rgb="'.$color
                .'"/><bgColor indexed="64"/></patternFill></fill>';
        }

        // Los rellenos 0 y 1 son obligatorios y reservados por el formato OOXML;
        // el 2 es el gris de las cabeceras y a partir del 3 van las bandas.
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="4">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="14"/><color rgb="FF1F3864"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'<font><sz val="10"/><color rgb="FF808080"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="'.(3 + count(self::BANDS)).'">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFEDEDED"/><bgColor indexed="64"/></patternFill></fill>'
            .$bandFills
            .'</fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color rgb="FFD0D0D0"/></left>'
            .'<right style="thin"><color rgb="FFD0D0D0"/></right>'
            .'<top style="thin"><color rgb="FFD0D0D0"/></top>'
            .'<bottom style="thin"><color rgb="FFD0D0D0"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="'.(count(self::INDEX) + count(self::BANDS)).'">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1">'
            .'<alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1">'
            .'<alignment vertical="top" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>'
            .'<xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1"/>'
            .self::bandXfs()
            .'</cellXfs></styleSheet>';
    }

    /** Índice de estilo de una banda de color ("blue", "green"…). */
    public static function band(string $name): int
    {
        $position = array_search($name, array_keys(self::BANDS), true);

        return count(self::INDEX) + (is_int($position) ? $position : 0);
    }

    private static function bandXfs(): string
    {
        $xfs = '';
        foreach (array_keys(self::BANDS) as $index => $name) {
            $xfs .= '<xf numFmtId="0" fontId="2" fillId="'.(3 + $index).'" borderId="1" xfId="0"'
                .' applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf>';
        }

        return $xfs;
    }
}
