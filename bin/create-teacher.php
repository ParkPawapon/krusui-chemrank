<?php

declare(strict_types=1);

use Domain\ValueObjects\Role;
use Infrastructure\Database\DatabaseConnection;
use Infrastructure\Persistence\PdoUserRepository;
use Infrastructure\Security\PasswordHasher;

require dirname(__DIR__) . '/bootstrap/app.php';

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$name = $argv[1] ?? null;
$username = $argv[2] ?? null;
$password = $argv[3] ?? null;

if (!$name || !$username || !$password) {
    fwrite(STDERR, "Usage: php bin/create-teacher.php \"Teacher Name\" username password\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

$pdo = (new DatabaseConnection())->pdo();
$users = new PdoUserRepository($pdo);

if ($users->usernameExists($username)) {
    fwrite(STDERR, "Username already exists.\n");
    exit(1);
}

$user = $users->create($name, Role::TEACHER, $username, (new PasswordHasher())->hash($password));
echo "Created teacher #{$user->id}: {$user->username}\n";

