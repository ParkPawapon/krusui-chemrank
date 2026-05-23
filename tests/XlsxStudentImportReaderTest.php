<?php

declare(strict_types=1);

use App\Services\XlsxStudentImportReader;

$xlsxPath = sys_get_temp_dir() . '/chemrank-import-reader-' . bin2hex(random_bytes(4)) . '.xlsx';

create_xlsx_import_test_file($xlsxPath, [
    ['เลขประจำตัวนักเรียน', 'ชื่อนักเรียน', 'ชั้น', 'ห้อง'],
    ['54321', 'เด็กนำเข้า', 'ม.5', '2'],
    ['54322', 'เด็กใช้รหัสกลาง', 'ม.5', '3'],
]);

try {
    $rows = (new XlsxStudentImportReader())->readRows($xlsxPath);

    assert_same(2, count($rows), 'Reader should parse student rows only');
    assert_same(2, $rows[0]['row'], 'First student should keep source row number');
    assert_same('54321', $rows[0]['student_number'], 'Student number should be read from xlsx');
    assert_same('เด็กนำเข้า', $rows[0]['full_name'], 'Thai student name should be read from xlsx');
    assert_same('ม.5', $rows[0]['class_level'], 'Class should be read separately');
    assert_same('2', $rows[0]['room'], 'Room should be read separately');
    assert_true(!array_key_exists('password', $rows[0]), 'Reader should not accept password from import files');
} finally {
    if (is_file($xlsxPath)) {
        unlink($xlsxPath);
    }
}

/**
 * @param array<int,array<int,string>> $rows
 */
function create_xlsx_import_test_file(string $path, array $rows): void
{
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Unable to create temporary xlsx file');
    }

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="students" sheetId="1" r:id="rId1"/></sheets>
</workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>');
    $zip->addFromString('xl/worksheets/sheet1.xml', create_import_sheet_xml($rows));
    $zip->close();
}

/**
 * @param array<int,array<int,string>> $rows
 */
function create_import_sheet_xml(array $rows): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

    foreach ($rows as $rowIndex => $row) {
        $rowNumber = $rowIndex + 1;
        $xml .= '<row r="' . $rowNumber . '">';

        foreach ($row as $columnIndex => $value) {
            $cellReference = import_column_name($columnIndex) . $rowNumber;
            $xml .= '<c r="' . $cellReference . '" t="inlineStr"><is><t>'
                . htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8')
                . '</t></is></c>';
        }

        $xml .= '</row>';
    }

    return $xml . '</sheetData></worksheet>';
}

function import_column_name(int $index): string
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
