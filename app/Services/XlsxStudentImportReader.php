<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use SimpleXMLElement;
use ZipArchive;

final class XlsxStudentImportReader
{
    private const XML_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * @return array<int,array{row:int,student_number:string,full_name:string,class_level:string,room:string}>
     */
    public function readRows(string $path, int $maxRows = 500): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new InvalidArgumentException('ระบบยังไม่พร้อมอ่านไฟล์ Excel กรุณาติดตั้ง PHP zip extension');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('ไม่สามารถเปิดไฟล์ Excel ได้');
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

            if ($sheetXml === false) {
                throw new InvalidArgumentException('ไฟล์ Excel ต้องมีแผ่นงานแรกสำหรับรายชื่อนักเรียน');
            }

            $rows = $this->parseSheetRows($sheetXml, $sharedStrings);
        } finally {
            $zip->close();
        }

        if ($rows === []) {
            return [];
        }

        $header = array_shift($rows);
        $columnMap = $this->mapHeader($header['cells']);
        $students = [];

        foreach ($rows as $row) {
            if (count($students) >= $maxRows) {
                throw new InvalidArgumentException('รองรับการนำเข้าสูงสุด ' . $maxRows . ' แถวต่อไฟล์');
            }

            $data = $this->rowToStudent($row['row'], $row['cells'], $columnMap);

            if ($this->isEmptyStudentRow($data)) {
                continue;
            }

            $students[] = $data;
        }

        return $students;
    }

    /**
     * @return array<int,string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $document = $this->loadXml($xml, 'ไม่สามารถอ่านข้อมูลตัวอักษรในไฟล์ Excel ได้');
        $document->registerXPathNamespace('x', self::XML_NS);
        $items = $document->xpath('//x:si') ?: [];
        $strings = [];

        foreach ($items as $item) {
            $item->registerXPathNamespace('x', self::XML_NS);
            $textNodes = $item->xpath('.//x:t') ?: [];
            $value = '';

            foreach ($textNodes as $textNode) {
                $value .= (string) $textNode;
            }

            $strings[] = trim($value);
        }

        return $strings;
    }

    /**
     * @param array<int,string> $sharedStrings
     * @return array<int,array{row:int,cells:array<int,string>}>
     */
    private function parseSheetRows(string $sheetXml, array $sharedStrings): array
    {
        $sheet = $this->loadXml($sheetXml, 'ไม่สามารถอ่านแผ่นงานในไฟล์ Excel ได้');
        $sheet->registerXPathNamespace('x', self::XML_NS);
        $xmlRows = $sheet->xpath('//x:sheetData/x:row') ?: [];
        $rows = [];

        foreach ($xmlRows as $xmlRow) {
            $xmlRow->registerXPathNamespace('x', self::XML_NS);
            $cells = [];

            foreach ($xmlRow->xpath('x:c') ?: [] as $cell) {
                $cell->registerXPathNamespace('x', self::XML_NS);
                $cells[$this->columnIndex((string) $cell['r'])] = $this->cellValue($cell, $sharedStrings);
            }

            ksort($cells);
            $rows[] = [
                'row' => (int) ($xmlRow['r'] ?? count($rows) + 1),
                'cells' => $cells,
            ];
        }

        return $rows;
    }

    /**
     * @param array<int,string> $cells
     * @return array<string,int>
     */
    private function mapHeader(array $cells): array
    {
        $knownHeaders = [
            'student_number' => ['เลขประจำตัวนักเรียน', 'เลขประจำตัว', 'รหัสนักเรียน', 'studentnumber', 'studentno', 'studentid'],
            'full_name' => ['ชื่อนักเรียน', 'ชื่อ-นามสกุล', 'ชื่อ', 'fullname', 'studentname', 'name'],
            'class_level' => ['ชั้น', 'ระดับชั้น', 'class', 'classlevel', 'grade'],
            'room' => ['ห้อง', 'room', 'classroom'],
        ];

        $columnMap = [];

        foreach ($cells as $index => $label) {
            $normalized = $this->normalizeHeader($label);

            foreach ($knownHeaders as $field => $headers) {
                foreach ($headers as $header) {
                    if ($normalized === $this->normalizeHeader($header)) {
                        $columnMap[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        foreach (['student_number', 'full_name', 'class_level', 'room'] as $requiredField) {
            if (!array_key_exists($requiredField, $columnMap)) {
                throw new InvalidArgumentException('ไฟล์ Excel ต้องมีคอลัมน์ เลขประจำตัวนักเรียน, ชื่อนักเรียน, ชั้น และห้อง');
            }
        }

        return $columnMap;
    }

    /**
     * @param array<int,string> $cells
     * @param array<string,int> $columnMap
     * @return array{row:int,student_number:string,full_name:string,class_level:string,room:string}
     */
    private function rowToStudent(int $rowNumber, array $cells, array $columnMap): array
    {
        return [
            'row' => $rowNumber,
            'student_number' => $this->cellAt($cells, $columnMap['student_number']),
            'full_name' => $this->cellAt($cells, $columnMap['full_name']),
            'class_level' => $this->cellAt($cells, $columnMap['class_level']),
            'room' => $this->cellAt($cells, $columnMap['room']),
        ];
    }

    /**
     * @param array{student_number:string,full_name:string,class_level:string,room:string} $row
     */
    private function isEmptyStudentRow(array $row): bool
    {
        return $row['student_number'] === ''
            && $row['full_name'] === ''
            && $row['class_level'] === ''
            && $row['room'] === '';
    }

    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');

        if ($type === 'inlineStr') {
            $textNodes = $cell->xpath('x:is/x:t') ?: [];

            return trim((string) ($textNodes[0] ?? ''));
        }

        $valueNodes = $cell->xpath('x:v') ?: [];
        $value = trim((string) ($valueNodes[0] ?? ''));

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $value;
    }

    private function columnIndex(string $cellReference): int
    {
        if (!preg_match('/^([A-Z]+)/i', $cellReference, $matches)) {
            return 0;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;

        for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    /**
     * @param array<int,string> $cells
     */
    private function cellAt(array $cells, int $index): string
    {
        return trim((string) ($cells[$index] ?? ''));
    }

    private function normalizeHeader(string $header): string
    {
        return mb_strtolower(str_replace([' ', '_', '-', '.', '/', "\n", "\r", "\t"], '', trim($header)));
    }

    private function loadXml(string $xml, string $errorMessage): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$document instanceof SimpleXMLElement) {
            throw new InvalidArgumentException($errorMessage);
        }

        return $document;
    }
}
