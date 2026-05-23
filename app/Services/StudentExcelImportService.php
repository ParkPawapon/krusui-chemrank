<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use Throwable;

final class StudentExcelImportService
{
    private const MAX_FILE_BYTES = 5_242_880;
    private const MAX_ROWS = 500;

    public function __construct(
        private readonly StudentService $students,
        private readonly XlsxStudentImportReader $reader,
    ) {
    }

    /**
     * @param array<string,mixed>|null $file
     */
    public function import(?array $file, int $academicYearId, string $defaultPassword, int $teacherId): StudentImportResult
    {
        $this->validateUpload($file);

        $defaultPassword = trim($defaultPassword);
        if (mb_strlen($defaultPassword) < 8) {
            throw new InvalidArgumentException('รหัสผ่านเริ่มต้นต้องมีอย่างน้อย 8 ตัวอักษร');
        }

        $rows = $this->reader->readRows((string) $file['tmp_name'], self::MAX_ROWS);
        if ($rows === []) {
            throw new InvalidArgumentException('ไฟล์นี้ยังไม่มีรายชื่อนักเรียนสำหรับนำเข้า');
        }

        $createdRows = 0;
        $errors = [];

        foreach ($rows as $row) {
            try {
                $this->students->createStudent(
                    $row['full_name'],
                    $row['student_number'],
                    $row['class_level'],
                    $row['room'],
                    $academicYearId,
                    $defaultPassword,
                    $teacherId
                );
                $createdRows++;
            } catch (InvalidArgumentException $exception) {
                $errors[] = 'แถว ' . $row['row'] . ': ' . $exception->getMessage();
            } catch (Throwable) {
                $errors[] = 'แถว ' . $row['row'] . ': ไม่สามารถเพิ่มนักเรียนได้';
            }
        }

        $visibleErrors = array_slice($errors, 0, 12);

        return new StudentImportResult(
            count($rows),
            $createdRows,
            count($errors),
            $visibleErrors,
            count($errors) > count($visibleErrors),
        );
    }

    /**
     * @param array<string,mixed>|null $file
     */
    public function preview(?array $file): array
    {
        $this->validateUpload($file);

        $rows = $this->reader->readRows((string) $file['tmp_name'], self::MAX_ROWS);
        if ($rows === []) {
            throw new InvalidArgumentException('ไฟล์นี้ยังไม่มีรายชื่อนักเรียนสำหรับนำเข้า');
        }

        return $rows;
    }

    /**
     * @param array<string,mixed>|null $file
     */
    private function validateUpload(?array $file): void
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('กรุณาเลือกไฟล์ Excel ก่อนนำเข้า');
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('อัปโหลดไฟล์ไม่สำเร็จ กรุณาลองใหม่');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? '');
        $size = (int) ($file['size'] ?? 0);

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new InvalidArgumentException('ไฟล์อัปโหลดไม่ถูกต้อง');
        }

        if ($size <= 0 || $size > self::MAX_FILE_BYTES) {
            throw new InvalidArgumentException('ไฟล์ Excel ต้องมีขนาดไม่เกิน 5 MB');
        }

        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new InvalidArgumentException('รองรับเฉพาะไฟล์ Excel นามสกุล .xlsx');
        }

        $mimeType = $this->detectMimeType($tmpName);
        $allowedMimeTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/octet-stream',
        ];

        if ($mimeType !== null && !in_array($mimeType, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException('ชนิดไฟล์ไม่ถูกต้อง กรุณาใช้ไฟล์ .xlsx');
        }
    }

    private function detectMimeType(string $path): ?string
    {
        if (!class_exists(\finfo::class)) {
            return null;
        }

        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($path);

        return is_string($mimeType) ? $mimeType : null;
    }
}
