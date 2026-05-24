<?php

declare(strict_types=1);

use App\Services\StudentRosterExportService;
use Domain\Entities\Student;
use Domain\Rank\RankRegistry;

$students = [
    new Student(1, 10, '12345', 'เด็กส่งออก หนึ่ง', 'ม.4', '1', 1, '2569', 12, '2026-05-24 00:00:00', '2026-05-24 00:00:00'),
    new Student(2, 11, '12346', 'เด็กส่งออก สอง', 'ม.5', '2', 1, '2569', 46, '2026-05-24 00:00:00', '2026-05-24 00:00:00'),
];

$export = (new StudentRosterExportService(new RankRegistry()))->create($students);
$xlsxPath = sys_get_temp_dir() . '/chemrank-roster-export-' . bin2hex(random_bytes(4)) . '.xlsx';

file_put_contents($xlsxPath, $export['content']);

try {
    assert_same('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $export['mimeType'], 'Roster export MIME type should be xlsx');
    assert_true(str_starts_with($export['filename'], 'chemrank-student-roster-'), 'Roster export filename should be stable and descriptive');
    assert_true(str_ends_with($export['filename'], '.xlsx'), 'Roster export filename should end with .xlsx');

    $zip = new ZipArchive();
    assert_true($zip->open($xlsxPath) === true, 'Roster export should be a readable xlsx zip file');

    $sheetXml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
    $stylesXml = (string) $zip->getFromName('xl/styles.xml');
    $workbookRelsXml = (string) $zip->getFromName('xl/_rels/workbook.xml.rels');
    $zip->close();

    assert_true(str_contains($sheetXml, 'เลขประจำตัวนักเรียน'), 'Roster export should include student number header');
    assert_true(str_contains($sheetXml, 'ชื่อ'), 'Roster export should include name header');
    assert_true(str_contains($sheetXml, 'ชั้น'), 'Roster export should include class header');
    assert_true(str_contains($sheetXml, 'จำนวนคะแนน'), 'Roster export should include score header');
    assert_true(str_contains($sheetXml, 'แรงก์'), 'Roster export should include rank header');
    assert_true(str_contains($sheetXml, '12345'), 'Roster export should include student number');
    assert_true(str_contains($sheetXml, 'เด็กส่งออก หนึ่ง'), 'Roster export should include Thai student name');
    assert_true(str_contains($sheetXml, 'ม.4/1'), 'Roster export should include combined class display');
    assert_true(str_contains($sheetXml, '<c r="D2" s="3"><v>12</v></c>'), 'Roster export should write score as a number');
    assert_true(str_contains($sheetXml, 'หยดต้นกำเนิด'), 'Roster export should include deterministic rank name');
    assert_true(str_contains($sheetXml, 'สารละลายก่อตัว'), 'Roster export should include rank at boundary');
    assert_true(str_contains($sheetXml, '<autoFilter ref="A1:E3"/>'), 'Roster export should include an Excel filter');
    assert_true(str_contains($stylesXml, 'FF69D7C6'), 'Roster export should include Chem Rank styling');
    assert_true(str_contains($workbookRelsXml, 'styles'), 'Roster export should attach styles to the workbook');
} finally {
    if (is_file($xlsxPath)) {
        unlink($xlsxPath);
    }
}
