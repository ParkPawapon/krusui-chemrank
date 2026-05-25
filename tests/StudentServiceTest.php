<?php

declare(strict_types=1);

use App\Services\StudentService;
use Domain\Rank\RankRegistry;
use Domain\ValueObjects\Role;
use Infrastructure\Persistence\PdoAcademicYearRepository;
use Infrastructure\Persistence\PdoActivityLogRepository;
use Infrastructure\Persistence\PdoDropTransactionRepository;
use Infrastructure\Persistence\PdoStudentRepository;
use Infrastructure\Persistence\PdoUserRepository;
use Infrastructure\Security\PasswordHasher;

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec((string) file_get_contents(base_path('database/schema.sqlite.sql')));

$users = new PdoUserRepository($pdo);
$students = new PdoStudentRepository($pdo);
$transactions = new PdoDropTransactionRepository($pdo);
$academicYears = new PdoAcademicYearRepository($pdo);
$activityLogs = new PdoActivityLogRepository($pdo);
$passwords = new PasswordHasher();
$ranks = new RankRegistry();
$service = new StudentService($pdo, $students, $users, $transactions, $academicYears, $activityLogs, $passwords, $ranks);

$academicYear = $academicYears->activeOrCreateDefault();
$teacher = $users->create('ครูซุย', Role::TEACHER, 'teacher', $passwords->hash('secure-password'));
$student = $service->createStudent('เด็กทดลอง', '12345', 'ม.4', '1', $academicYear->id, 'secure-password', $teacher->id);

assert_same(0, $student->drops, 'New student should start at zero drops');
assert_same('12345', $student->studentNumber, 'Student number should be stored');
assert_same('ม.4', $student->classLevel, 'Class should be stored separately');
assert_same('1', $student->room, 'Room should be stored separately');
assert_same('ม.4/1', $student->className, 'Class display should be derived from class and room');
assert_same($academicYear->name, $student->academicYearName, 'Student should keep academic year context');
assert_true($users->findByUsername('12345') !== null, 'Student account should use student number as login identifier');

$numericClassStudent = $service->createStudent('เด็กเลข', '12346', '4', '02', $academicYear->id, 'secure-password', $teacher->id);
assert_same('ม.4', $numericClassStudent->classLevel, 'Numeric class input should be normalized for display');
assert_same('2', $numericClassStudent->room, 'Numeric room input should be normalized');
assert_same('ม.4/2', $numericClassStudent->className, 'Normalized class and room should render as a classroom');

try {
    $service->createStudent('เลขผิด', '1234A', 'ม.4', '1', $academicYear->id, 'secure-password', $teacher->id);
    throw new RuntimeException('Invalid student number should fail validation');
} catch (InvalidArgumentException) {
}

try {
    $service->createStudent('เลขซ้ำ', '12345', 'ม.4', '2', $academicYear->id, 'secure-password', $teacher->id);
    throw new RuntimeException('Duplicate student number should fail validation');
} catch (InvalidArgumentException) {
}

$added = $service->adjustDrops($student->id, $teacher->id, 12, 'add', 'ตอบคำถามถูก');
assert_same(12, $added->newDrops, 'Add drops should update total');
assert_same('หยดต้นกำเนิด', $added->newRank->thaiName, 'Add should calculate new rank');
assert_true($added->rankChanged(), 'Adding 12 should change rank from First Drop');

$subtracted = $service->adjustDrops($student->id, $teacher->id, 99, 'subtract', 'ปรับยอด');
assert_same(0, $subtracted->newDrops, 'Subtract should never go below zero');
assert_same('ละอองแรก', $subtracted->newRank->thaiName, 'Subtract should recalculate rank');

$count = (int) $pdo->query('SELECT COUNT(*) FROM drop_transactions')->fetchColumn();
assert_same(2, $count, 'Every drop adjustment should be logged');

$activityLogCount = (int) $pdo->query('SELECT COUNT(*) FROM activity_logs')->fetchColumn();
assert_same(4, $activityLogCount, 'Student creation and every drop adjustment should write activity logs');

$resetStudent = $service->resetStudentPassword($student->id, $teacher->id, '12345678');
assert_same($student->id, $resetStudent->id, 'Password reset should return the reset student');
$resetStudentUser = $users->findByUsername('12345');
assert_true($resetStudentUser !== null, 'Student user should exist after password reset');
assert_true($passwords->verify('12345678', $resetStudentUser->passwordHash), 'Student password should reset to the configured default');

$passwordResetLogCount = (int) $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action = 'student.password_reset'")->fetchColumn();
assert_same(1, $passwordResetLogCount, 'Student password reset should write an activity log');

$users->create('บัญชีนักเรียนค้าง', Role::STUDENT, '54321', $passwords->hash('secure-password'));
$service->deleteStudent($student->id, $teacher->id);
assert_true($students->findById($student->id) === null, 'Deleted student should be removed from the roster');
assert_true($users->findByUsername('12345') === null, 'Deleting a student should remove the linked student user account');
assert_true($users->findByUsername('54321') === null, 'Deleting a student should clean orphaned student user accounts');
assert_true($users->findByUsername('12346') !== null, 'Deleting one student must not remove active student accounts');

try {
    $service->adjustDrops($student->id, $teacher->id, 0, 'add', null);
    throw new RuntimeException('Zero amount should fail validation');
} catch (InvalidArgumentException) {
}
