<?php

declare(strict_types=1);

use App\Services\UserPasswordService;
use Domain\ValueObjects\Role;
use Infrastructure\Persistence\PdoActivityLogRepository;
use Infrastructure\Persistence\PdoUserRepository;
use Infrastructure\Security\PasswordHasher;

$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec((string) file_get_contents(base_path('database/schema.sqlite.sql')));

$users = new PdoUserRepository($pdo);
$activityLogs = new PdoActivityLogRepository($pdo);
$passwords = new PasswordHasher();
$service = new UserPasswordService($users, $passwords, $activityLogs);
$student = $users->create('เด็กทดลอง', Role::STUDENT, '12345', $passwords->hash('old-password'));

try {
    $service->changePassword($student, 'wrong-password', 'new-password', 'new-password');
    throw new RuntimeException('Wrong current password should fail validation');
} catch (InvalidArgumentException) {
}

try {
    $service->changePassword($student, 'old-password', 'short', 'short');
    throw new RuntimeException('Short new password should fail validation');
} catch (InvalidArgumentException) {
}

try {
    $service->changePassword($student, 'old-password', 'new-password', 'different-password');
    throw new RuntimeException('Password confirmation mismatch should fail validation');
} catch (InvalidArgumentException) {
}

$service->changePassword($student, 'old-password', 'new-password', 'new-password');
$updated = $users->findByUsername('12345');

assert_true($updated !== null, 'Updated user should still exist');
assert_true($passwords->verify('new-password', $updated->passwordHash), 'New password should verify');
assert_true(!$passwords->verify('old-password', $updated->passwordHash), 'Old password should no longer verify');

$activityLogCount = (int) $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE action = 'password.changed'")->fetchColumn();
assert_same(1, $activityLogCount, 'Password change should write one activity log');
