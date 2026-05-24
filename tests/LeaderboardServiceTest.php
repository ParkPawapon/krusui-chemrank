<?php

declare(strict_types=1);

use App\Services\LeaderboardService;
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
$studentService = new StudentService($pdo, $students, $users, $transactions, $academicYears, $activityLogs, $passwords, $ranks);
$leaderboard = new LeaderboardService($students, $ranks);

$academicYear = $academicYears->activeOrCreateDefault();
$teacher = $users->create('ครูซุย', Role::TEACHER, 'teacher', $passwords->hash('secure-password'));

$firstTieA = $studentService->createStudent('เด็กอันดับร่วม หนึ่ง', '21001', 'ม.4', '1', $academicYear->id, 'secure-password', $teacher->id);
$firstTieB = $studentService->createStudent('เด็กอันดับร่วม สอง', '21002', 'ม.4', '1', $academicYear->id, 'secure-password', $teacher->id);
$thirdPlace = $studentService->createStudent('เด็กอันดับสาม', '21003', 'ม.4', '1', $academicYear->id, 'secure-password', $teacher->id);
$fourthPlace = $studentService->createStudent('เด็กอันดับสี่', '21004', 'ม.4', '1', $academicYear->id, 'secure-password', $teacher->id);

$studentService->adjustDrops($firstTieA->id, $teacher->id, 30, 'add', null);
$studentService->adjustDrops($firstTieB->id, $teacher->id, 30, 'add', null);
$studentService->adjustDrops($thirdPlace->id, $teacher->id, 20, 'add', null);
$studentService->adjustDrops($fourthPlace->id, $teacher->id, 10, 'add', null);

$ranked = $leaderboard->ranked('ม.4/1');

assert_same(1, $ranked[0]['place'], 'First tied student should be rank 1');
assert_true((bool) $ranked[0]['is_tied'], 'First member of a tie should be marked as tied');
assert_same(1, $ranked[1]['place'], 'Second tied student should share rank 1');
assert_true((bool) $ranked[1]['is_tied'], 'Second member of a tie should be marked as tied');
assert_same(3, $ranked[2]['place'], 'Competition ranking should skip rank 2 after a two-person rank 1 tie');
assert_true(!(bool) $ranked[2]['is_tied'], 'A single rank 3 student should not be marked as tied');
assert_same(4, $ranked[3]['place'], 'Next student should be rank 4');
