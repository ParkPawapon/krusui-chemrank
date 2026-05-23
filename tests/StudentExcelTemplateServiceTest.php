<?php

declare(strict_types=1);

use App\Services\StudentExcelTemplateService;
use App\Services\XlsxStudentImportReader;

$template = (new StudentExcelTemplateService())->create();
$xlsxPath = sys_get_temp_dir() . '/chemrank-template-service-' . bin2hex(random_bytes(4)) . '.xlsx';

file_put_contents($xlsxPath, $template['content']);

try {
    assert_same('chemrank-student-import-template.xlsx', $template['filename'], 'Template filename should be stable');
    assert_same('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $template['mimeType'], 'Template MIME type should be xlsx');

    $rows = (new XlsxStudentImportReader())->readRows($xlsxPath);

    assert_same(2, count($rows), 'Template should include two readable sample rows');
    assert_same('12345', $rows[0]['student_number'], 'Template should include a valid 5-digit student number');
    assert_same('เด็กตัวอย่าง หนึ่ง', $rows[0]['full_name'], 'Template should include a Thai student name');
    assert_same('ม.4', $rows[0]['class_level'], 'Template should include a class level');
    assert_same('1', $rows[0]['room'], 'Template should include a room');
    assert_true(!array_key_exists('password', $rows[0]), 'Template should not include a password column');
} finally {
    if (is_file($xlsxPath)) {
        unlink($xlsxPath);
    }
}
