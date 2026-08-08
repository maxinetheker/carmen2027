<?php

namespace App\Services\Export;

use RuntimeException;
use ZipArchive;

/**
 * Escribe un .xlsx real sin depender de librerías externas.
 *
 * Un xlsx no es más que un ZIP con unos cuantos XML dentro, y el hosting ya trae
 * la extensión zip de PHP; instalar PhpSpreadsheet solo para volcar unas tablas
 * habría sumado varios megas de dependencias al despliegue.
 */
class XlsxWorkbook
{
    /** @var XlsxSheet[] */
    private array $sheets = [];

    public function sheet(string $name): XlsxSheet
    {
        // Excel rechaza nombres con : \ / ? * [ ] o de más de 31 caracteres.
        $clean = mb_substr(str_replace([':', '\\', '/', '?', '*', '[', ']'], ' ', $name), 0, 31);

        return $this->sheets[] = new XlsxSheet($clean);
    }

    public function save(string $path): string
    {
        if (! $this->sheets) {
            $this->sheet('Hoja 1')->row(['Sin datos']);
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo de Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', XlsxStyles::xml());
        foreach ($this->sheets as $index => $sheet) {
            $zip->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', $sheet->xml());
        }
        $zip->close();

        return $path;
    }

    private function contentTypes(): string
    {
        $overrides = '';
        foreach ($this->sheets as $index => $sheet) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.($index + 1).'.xml"'
                .' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$overrides.'</Types>';
    }

    private function workbook(): string
    {
        $entries = '';
        foreach ($this->sheets as $index => $sheet) {
            $entries .= '<sheet name="'.htmlspecialchars($sheet->name, ENT_QUOTES | ENT_XML1)
                .'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            ."<sheets>{$entries}</sheets></workbook>";
    }

    private function workbookRels(): string
    {
        $relationships = '';
        foreach ($this->sheets as $index => $sheet) {
            $relationships .= '<Relationship Id="rId'.($index + 1).'"'
                .' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                .' Target="worksheets/sheet'.($index + 1).'.xml"/>';
        }
        $styleId = count($this->sheets) + 1;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relationships
            .'<Relationship Id="rId'.$styleId.'"'
            .' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
            .' Target="styles.xml"/></Relationships>';
    }
}
