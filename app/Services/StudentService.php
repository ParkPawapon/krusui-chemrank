<?php

declare(strict_types=1);

namespace App\Services;

use Domain\Entities\Student;
use Domain\Repositories\AcademicYearRepository;
use Domain\Repositories\ActivityLogRepository;
use Domain\Repositories\DropTransactionRepository;
use Domain\Repositories\StudentRepository;
use Domain\Repositories\UserRepository;
use Domain\Rank\RankRegistry;
use Domain\ValueObjects\Role;
use Infrastructure\Security\PasswordHasher;
use PDO;

final class StudentService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly StudentRepository $students,
        private readonly UserRepository $users,
        private readonly DropTransactionRepository $transactions,
        private readonly AcademicYearRepository $academicYears,
        private readonly ActivityLogRepository $activityLogs,
        private readonly PasswordHasher $passwords,
        private readonly RankRegistry $ranks,
    ) {
    }

    public function createStudent(
        string $fullName,
        string $studentNumber,
        string $classLevel,
        string $room,
        int $academicYearId,
        string $password,
        int $teacherId,
    ): Student {
        $fullName = trim($fullName);
        $studentNumber = trim($studentNumber);
        $classLevel = trim($classLevel);
        $room = trim($room);
        $password = trim($password);

        if ($fullName === '' || mb_strlen($fullName) > 160) {
            throw new \InvalidArgumentException('กรุณากรอกชื่อนักเรียนให้ถูกต้อง');
        }

        if (!preg_match('/^\d{5}$/', $studentNumber)) {
            throw new \InvalidArgumentException('เลขประจำตัวนักเรียนต้องเป็นตัวเลข 5 หลัก');
        }

        $classLevel = $this->normalizeClassLevel($classLevel);
        $room = $this->normalizeRoom($room);

        if ($password === '' || mb_strlen($password) < 8) {
            throw new \InvalidArgumentException('รหัสผ่านนักเรียนต้องมีอย่างน้อย 8 ตัวอักษร');
        }

        if (!$this->academicYears->findById($academicYearId)) {
            throw new \InvalidArgumentException('ปีการศึกษาไม่ถูกต้อง');
        }

        if ($this->students->studentNumberExists($studentNumber) || $this->users->usernameExists($studentNumber)) {
            throw new \InvalidArgumentException('เลขประจำตัวนักเรียนนี้ถูกใช้แล้ว');
        }

        $this->pdo->beginTransaction();

        try {
            $user = $this->users->create($fullName, Role::STUDENT, $studentNumber, $this->passwords->hash($password));
            $student = $this->students->create($fullName, $studentNumber, $classLevel, $room, $academicYearId, $user->id, 0);
            $this->activityLogs->record($teacherId > 0 ? $teacherId : null, 'student.created', 'student', $student->id, [
                'student_number' => $student->studentNumber,
                'class' => $student->classLevel,
                'room' => $student->room,
                'academic_year_id' => $student->academicYearId,
            ]);
            $this->pdo->commit();

            return $student;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function normalizeClassLevel(string $classLevel): string
    {
        $classLevel = trim($classLevel);

        if (preg_match('/^\d{1,2}$/', $classLevel)) {
            $number = (int) $classLevel;
            if ($number >= 1) {
                return 'ม.' . $number;
            }
        }

        if (preg_match('/^ม\.(\d{1,2})$/u', $classLevel, $matches)) {
            $number = (int) $matches[1];
            if ($number >= 1) {
                return 'ม.' . $number;
            }
        }

        throw new \InvalidArgumentException('ชั้นเรียนต้องเป็นตัวเลข เช่น 4');
    }

    private function normalizeRoom(string $room): string
    {
        $room = trim($room);

        if (preg_match('/^\d{1,2}$/', $room)) {
            $number = (int) $room;
            if ($number >= 1) {
                return (string) $number;
            }
        }

        throw new \InvalidArgumentException('ห้องเรียนต้องเป็นตัวเลข เช่น 1');
    }

    public function adjustDrops(int $studentId, int $teacherId, int $amount, string $type, ?string $reason): DropAdjustmentResult
    {
        if (!in_array($type, ['add', 'subtract'], true)) {
            throw new \InvalidArgumentException('ประเภทการปรับหยดไม่ถูกต้อง');
        }

        if ($amount < 1 || $amount > 999) {
            throw new \InvalidArgumentException('จำนวนหยดต้องอยู่ระหว่าง 1 ถึง 999');
        }

        $this->pdo->beginTransaction();

        try {
            $student = $this->students->findById($studentId);

            if (!$student) {
                throw new \InvalidArgumentException('ไม่พบนักเรียนที่เลือก');
            }

            $previousDrops = $student->drops;
            $newDrops = $type === 'add'
                ? $student->drops + $amount
                : max(0, $student->drops - $amount);

            $previousRank = $this->ranks->forDrops($previousDrops);
            $updated = $this->students->updateDrops($studentId, $newDrops);
            $this->transactions->create($studentId, $teacherId, $amount, $type, $reason);
            $newRank = $this->ranks->forDrops($updated->drops);
            $progress = $this->ranks->progress($updated->drops);
            $this->activityLogs->record($teacherId > 0 ? $teacherId : null, 'drops.adjusted', 'student', $studentId, [
                'type' => $type,
                'amount' => $amount,
                'reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
                'previous_drops' => $previousDrops,
                'new_drops' => $updated->drops,
                'previous_rank' => $previousRank->thaiName,
                'new_rank' => $newRank->thaiName,
            ]);

            $this->pdo->commit();

            return new DropAdjustmentResult($updated, $previousDrops, $updated->drops, $previousRank, $newRank, $progress);
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function deleteStudent(int $studentId, int $teacherId): void
    {
        if ($studentId < 1) {
            throw new \InvalidArgumentException('ไม่พบนักเรียนที่เลือก');
        }

        $student = $this->students->findById($studentId);

        if (!$student) {
            throw new \InvalidArgumentException('ไม่พบนักเรียนที่เลือก');
        }

        $this->pdo->beginTransaction();

        try {
            $this->students->delete($studentId);
            $this->activityLogs->record($teacherId > 0 ? $teacherId : null, 'student.deleted', 'student', $studentId, [
                'student_number' => $student->studentNumber,
                'full_name' => $student->fullName,
                'class' => $student->classLevel,
                'room' => $student->room,
                'academic_year_id' => $student->academicYearId,
            ]);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function resetStudentPassword(int $studentId, int $teacherId, string $password): Student
    {
        $password = trim($password);

        if ($studentId < 1) {
            throw new \InvalidArgumentException('ไม่พบนักเรียนที่เลือก');
        }

        if (mb_strlen($password) < 8) {
            throw new \InvalidArgumentException('รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร');
        }

        $student = $this->students->findById($studentId);

        if (!$student) {
            throw new \InvalidArgumentException('ไม่พบนักเรียนที่เลือก');
        }

        if ($student->userId === null) {
            throw new \InvalidArgumentException('ไม่พบบัญชีเข้าสู่ระบบของนักเรียน');
        }

        $this->pdo->beginTransaction();

        try {
            $this->users->updatePasswordHash($student->userId, $this->passwords->hash($password));
            $this->activityLogs->record($teacherId > 0 ? $teacherId : null, 'student.password_reset', 'student', $studentId, [
                'student_number' => $student->studentNumber,
                'full_name' => $student->fullName,
                'user_id' => $student->userId,
            ]);
            $this->pdo->commit();

            return $student;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
