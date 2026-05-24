<?php

declare(strict_types=1);

namespace App\Services;

use Domain\Entities\Student;
use Domain\Rank\RankRegistry;
use RuntimeException;
use ZipArchive;

final class StudentRosterExportService
{
    private const MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct(private readonly RankRegistry $ranks)
    {
    }

    /**
     * @param array<int,Student> $students
     * @return array{content:string,mimeType:string,filename:string}
     */
    public function create(array $students): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP zip extension is required to export Excel files.');
        }

        $path = tempnam(sys_get_temp_dir(), 'chemrank-roster-');
        if ($path === false) {
            throw new RuntimeException('Unable to create temporary roster export.');
        }

        $zip = new ZipArchive();
        $opened = false;

        try {
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to write roster export.');
            }

            $opened = true;
            $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
            $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
            $zip->addFromString('xl/workbook.xml', $this->workbookXml());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
            $zip->addFromString('xl/styles.xml', $this->stylesXml());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml($students));

            if (!$zip->close()) {
                throw new RuntimeException('Unable to finalize roster export.');
            }

            $opened = false;
            $content = file_get_contents($path);
            if ($content === false) {
                throw new RuntimeException('Unable to read roster export.');
            }

            return [
                'content' => $content,
                'mimeType' => self::MIME_TYPE,
                'filename' => 'chemrank-student-roster-' . date('Ymd-His') . '.xlsx',
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
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
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
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            . '<font><sz val="11"/><color theme="1"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF28344F"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF69D7C6"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFF3DC"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFE8DEC8"/></left><right style="thin"><color rgb="FFE8DEC8"/></right><top style="thin"><color rgb="FFE8DEC8"/></top><bottom style="thin"><color rgb="FFE8DEC8"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '<dxfs count="0"/>'
            . '<tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>'
            . '</styleSheet>';
    }

    /**
     * @param array<int,Student> $students
     */
    private function sheetXml(array $students): string
    {
        $rows = [['เลขประจำตัวนักเรียน', 'ชื่อ', 'ชั้น', 'จำนวนคะแนน', 'แรงก์']];

        foreach ($students as $student) {
            $rows[] = [
                $student->studentNumber,
                $student->fullName,
                $student->className,
                $student->drops,
                $this->ranks->forDrops($student->drops)->thaiName,
            ];
        }

        $lastRow = max(1, count($rows));
        $dimension = 'A1:E' . $lastRow;
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="' . $dimension . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A2" sqref="A2"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="20"/>'
            . '<cols>'
            . '<col min="1" max="1" width="24" customWidth="1"/>'
            . '<col min="2" max="2" width="34" customWidth="1"/>'
            . '<col min="3" max="3" width="18" customWidth="1"/>'
            . '<col min="4" max="4" width="16" customWidth="1"/>'
            . '<col min="5" max="5" width="24" customWidth="1"/>'
            . '</cols>'
            . '<sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $xml .= '<row r="' . $rowNumber . '" ht="' . ($rowNumber === 1 ? '24' : '22') . '" customHeight="1">';

            foreach ($row as $columnIndex => $value) {
                $cell = $this->columnName($columnIndex) . $rowNumber;
                $style = $rowNumber === 1 ? 1 : ($columnIndex === 3 ? 3 : 2);

                if ($columnIndex === 3 && $rowNumber > 1) {
                    $xml .= '<c r="' . $cell . '" s="' . $style . '"><v>' . max(0, (int) $value) . '</v></c>';
                } else {
                    $xml .= '<c r="' . $cell . '" s="' . $style . '" t="inlineStr"><is><t>'
                        . htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8')
                        . '</t></is></c>';
                }
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData>';
        $xml .= '<autoFilter ref="' . $dimension . '"/>';

        return $xml . '</worksheet>';
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
