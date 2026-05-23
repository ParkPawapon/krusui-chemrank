<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use ZipArchive;

final class StudentExcelTemplateService
{
    private const MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * @return array{content:string,mimeType:string,filename:string}
     */
    public function create(): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP zip extension is required to create Excel templates.');
        }

        $path = tempnam(sys_get_temp_dir(), 'chemrank-template-');
        if ($path === false) {
            throw new RuntimeException('Unable to create temporary Excel template.');
        }

        $zip = new ZipArchive();
        $opened = false;

        try {
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to write Excel template.');
            }

            $opened = true;
            $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
            $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
            $zip->addFromString('xl/workbook.xml', $this->workbookXml());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());

            if (!$zip->close()) {
                throw new RuntimeException('Unable to finalize Excel template.');
            }

            $opened = false;
            $content = file_get_contents($path);
            if ($content === false) {
                throw new RuntimeException('Unable to read Excel template.');
            }

            return [
                'content' => $content,
                'mimeType' => self::MIME_TYPE,
                'filename' => 'chemrank-student-import-template.xlsx',
            ];
        } finally {
            if ($opened) {
                $zip->close();
            }

            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="students" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';
    }

    private function sheetXml(): string
    {
        $rows = [
            ['เลขประจำตัวนักเรียน', 'ชื่อนักเรียน', 'ชั้น', 'ห้อง'],
            ['12345', 'เด็กตัวอย่าง หนึ่ง', 'ม.4', '1'],
            ['12346', 'เด็กตัวอย่าง สอง', 'ม.4', '2'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:D3"/>'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<cols><col min="1" max="1" width="24" customWidth="1"/><col min="2" max="2" width="28" customWidth="1"/><col min="3" max="4" width="14" customWidth="1"/></cols>'
            . '<sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $xml .= '<row r="' . $rowNumber . '">';

            foreach ($row as $columnIndex => $value) {
                $cell = $this->columnName($columnIndex) . $rowNumber;
                $xml .= '<c r="' . $cell . '" t="inlineStr"><is><t>'
                    . htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8')
                    . '</t></is></c>';
            }

            $xml .= '</row>';
        }

        return $xml . '</sheetData></worksheet>';
    }

    private function columnName(int $index): string
    {
        $name = '';
        $index++;

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}
