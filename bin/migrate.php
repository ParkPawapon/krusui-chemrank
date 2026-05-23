<?php

declare(strict_types=1);

use Infrastructure\Database\DatabaseConnection;

require dirname(__DIR__) . '/bootstrap/app.php';

$pdo = (new DatabaseConnection())->pdo();
$connection = (string) config('database.connection', 'sqlite');
$isMysql = $connection === 'mysql';
$schema = $isMysql
    ? base_path('database/schema.mysql.sql')
    : base_path('database/schema.sqlite.sql');

$sql = file_get_contents($schema);

if ($sql === false) {
    fwrite(STDERR, "Schema file not found.\n");
    exit(1);
}

try {
    $pdo->exec($sql);
} catch (PDOException $exception) {
    $message = $exception->getMessage();

    if (!str_contains($message, 'no such column: class')) {
        throw $exception;
    }
}

backfillStudentProfileSchema($pdo, $isMysql);

echo "Migrated using {$schema}\n";

function backfillStudentProfileSchema(PDO $pdo, bool $isMysql): void
{
    $academicYearId = ensureActiveAcademicYear($pdo);

    ensureColumn($pdo, $isMysql, 'students', 'student_number', $isMysql ? 'CHAR(5) NULL' : 'TEXT NULL');
    ensureColumn($pdo, $isMysql, 'students', 'class', $isMysql ? 'VARCHAR(20) NULL' : 'TEXT NULL', true);
    ensureColumn($pdo, $isMysql, 'students', 'room', $isMysql ? 'VARCHAR(20) NULL' : 'TEXT NULL');
    ensureColumn($pdo, $isMysql, 'students', 'academic_year_id', $isMysql ? 'BIGINT UNSIGNED NULL' : 'INTEGER NULL');

    backfillClassAndRoom($pdo);
    backfillAcademicYear($pdo, $academicYearId);
    backfillStudentNumbers($pdo);
    hardenStudentSchema($pdo, $isMysql);
}

function ensureActiveAcademicYear(PDO $pdo): int
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM academic_years')->fetchColumn();

    if ($count === 0) {
        $statement = $pdo->prepare(
            'INSERT INTO academic_years (name, is_active, created_at, updated_at)
             VALUES (:name, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $statement->execute(['name' => (string) ((int) date('Y') + 543)]);
    }

    $active = $pdo->query('SELECT id FROM academic_years WHERE is_active = 1 ORDER BY id DESC LIMIT 1')->fetchColumn();

    if ($active !== false) {
        return (int) $active;
    }

    $id = (int) $pdo->query('SELECT id FROM academic_years ORDER BY id DESC LIMIT 1')->fetchColumn();
    $statement = $pdo->prepare('UPDATE academic_years SET is_active = 1 WHERE id = :id');
    $statement->execute(['id' => $id]);

    return $id;
}

function ensureColumn(PDO $pdo, bool $isMysql, string $table, string $column, string $definition, bool $quoteColumn = false): void
{
    if (in_array($column, columns($pdo, $isMysql, $table), true)) {
        return;
    }

    $columnSql = $quoteColumn ? "`{$column}`" : $column;
    safeExec($pdo, sprintf('ALTER TABLE %s ADD COLUMN %s %s', $table, $columnSql, $definition));
}

/**
 * @return array<int,string>
 */
function columns(PDO $pdo, bool $isMysql, string $table): array
{
    if ($isMysql) {
        $statement = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');

        return array_map(static fn (array $row): string => (string) $row['Field'], $statement->fetchAll());
    }

    $statement = $pdo->query('PRAGMA table_info(' . $table . ')');

    return array_map(static fn (array $row): string => (string) $row['name'], $statement->fetchAll());
}

function backfillClassAndRoom(PDO $pdo): void
{
    $studentColumns = columns($pdo, driverIsMysql($pdo), 'students');
    $hasLegacyClassName = in_array('class_name', $studentColumns, true);
    $select = $hasLegacyClassName
        ? 'SELECT id, class_name, `class`, room FROM students'
        : 'SELECT id, NULL AS class_name, `class`, room FROM students';
    $rows = $pdo->query($select)->fetchAll();
    $statement = $pdo->prepare('UPDATE students SET `class` = :class_level, room = :room WHERE id = :id');

    foreach ($rows as $row) {
        $classLevel = trim((string) ($row['class'] ?? ''));
        $room = trim((string) ($row['room'] ?? ''));

        if ($classLevel !== '' && $room !== '') {
            continue;
        }

        [$legacyClassLevel, $legacyRoom] = splitClassName((string) ($row['class_name'] ?? ''));
        $statement->execute([
            'class_level' => $classLevel !== '' ? $classLevel : $legacyClassLevel,
            'room' => $room !== '' ? $room : $legacyRoom,
            'id' => (int) $row['id'],
        ]);
    }
}

function backfillAcademicYear(PDO $pdo, int $academicYearId): void
{
    $statement = $pdo->prepare('UPDATE students SET academic_year_id = :academic_year_id WHERE academic_year_id IS NULL OR academic_year_id = 0');
    $statement->execute(['academic_year_id' => $academicYearId]);
}

function backfillStudentNumbers(PDO $pdo): void
{
    $rows = $pdo->query(
        'SELECT s.id, s.user_id, s.student_number, u.username
         FROM students s
         LEFT JOIN users u ON u.id = s.user_id
         ORDER BY s.id ASC'
    )->fetchAll();
    $usedStudentNumbers = [];

    $updateStudent = $pdo->prepare('UPDATE students SET student_number = :student_number WHERE id = :id');
    $updateUser = $pdo->prepare('UPDATE users SET username = :username, updated_at = CURRENT_TIMESTAMP WHERE id = :id');

    foreach ($rows as $row) {
        $studentId = (int) $row['id'];
        $userId = $row['user_id'] !== null ? (int) $row['user_id'] : null;
        $currentNumber = trim((string) ($row['student_number'] ?? ''));
        $currentUsername = trim((string) ($row['username'] ?? ''));

        if (isStudentNumber($currentNumber) && !isset($usedStudentNumbers[$currentNumber]) && usernameAvailableFor($pdo, $currentNumber, $userId)) {
            $studentNumber = $currentNumber;
        } elseif (isStudentNumber($currentUsername) && !isset($usedStudentNumbers[$currentUsername])) {
            $studentNumber = $currentUsername;
        } else {
            $studentNumber = nextStudentNumber($studentId, $usedStudentNumbers, $pdo, $userId);
        }

        $usedStudentNumbers[$studentNumber] = true;
        $updateStudent->execute(['student_number' => $studentNumber, 'id' => $studentId]);

        if ($userId !== null && $currentUsername !== $studentNumber && usernameAvailableFor($pdo, $studentNumber, $userId)) {
            $updateUser->execute(['username' => $studentNumber, 'id' => $userId]);
        }
    }
}

function hardenStudentSchema(PDO $pdo, bool $isMysql): void
{
    if (!hasUniqueIndexOnColumn($pdo, $isMysql, 'students', 'student_number')) {
        safeExec($pdo, 'CREATE UNIQUE INDEX idx_students_student_number_unique ON students(student_number)');
    }

    safeExec($pdo, 'CREATE INDEX idx_students_class_room ON students(`class`, room)');
    safeExec($pdo, 'CREATE INDEX idx_students_academic_year_id ON students(academic_year_id)');
    safeExec($pdo, 'CREATE INDEX idx_activity_logs_actor_user_id ON activity_logs(actor_user_id)');
    safeExec($pdo, 'CREATE INDEX idx_activity_logs_entity ON activity_logs(entity_type, entity_id)');

    if (!$isMysql) {
        return;
    }

    safeExec($pdo, 'ALTER TABLE students MODIFY student_number CHAR(5) NOT NULL');
    safeExec($pdo, 'ALTER TABLE students MODIFY `class` VARCHAR(20) NOT NULL');
    safeExec($pdo, 'ALTER TABLE students MODIFY room VARCHAR(20) NOT NULL');
    safeExec($pdo, 'ALTER TABLE students MODIFY academic_year_id BIGINT UNSIGNED NOT NULL');
}

function hasUniqueIndexOnColumn(PDO $pdo, bool $isMysql, string $table, string $column): bool
{
    if ($isMysql) {
        $statement = $pdo->query(sprintf('SHOW INDEX FROM `%s` WHERE Column_name = %s', $table, $pdo->quote($column)));

        foreach ($statement->fetchAll() as $row) {
            if ((int) $row['Non_unique'] === 0) {
                return true;
            }
        }

        return false;
    }

    $indexes = $pdo->query('PRAGMA index_list(' . $table . ')')->fetchAll();

    foreach ($indexes as $index) {
        if ((int) ($index['unique'] ?? 0) !== 1) {
            continue;
        }

        $indexName = (string) $index['name'];
        $columns = $pdo->query('PRAGMA index_info(' . $indexName . ')')->fetchAll();

        foreach ($columns as $row) {
            if ((string) $row['name'] === $column) {
                return true;
            }
        }
    }

    return false;
}

/**
 * @return array{0:string,1:string}
 */
function splitClassName(string $className): array
{
    $className = trim($className);

    if ($className === '') {
        return ['ไม่ระบุ', '-'];
    }

    if (!str_contains($className, '/')) {
        return [$className, '-'];
    }

    [$classLevel, $room] = explode('/', $className, 2);
    $classLevel = trim($classLevel);
    $room = trim($room);

    return [$classLevel !== '' ? $classLevel : 'ไม่ระบุ', $room !== '' ? $room : '-'];
}

function isStudentNumber(string $value): bool
{
    return preg_match('/^\d{5}$/', $value) === 1;
}

function usernameAvailableFor(PDO $pdo, string $username, ?int $userId): bool
{
    $statement = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $statement->execute(['username' => $username]);
    $existingId = $statement->fetchColumn();

    return $existingId === false || ($userId !== null && (int) $existingId === $userId);
}

function nextStudentNumber(int $seed, array $usedStudentNumbers, PDO $pdo, ?int $userId): string
{
    $start = (($seed - 1) % 99999) + 1;

    for ($offset = 0; $offset < 99999; $offset++) {
        $candidate = str_pad((string) (((($start + $offset) - 1) % 99999) + 1), 5, '0', STR_PAD_LEFT);

        if (!isset($usedStudentNumbers[$candidate]) && usernameAvailableFor($pdo, $candidate, $userId)) {
            return $candidate;
        }
    }

    throw new RuntimeException('Unable to generate student number.');
}

function safeExec(PDO $pdo, string $sql): void
{
    try {
        $pdo->exec($sql);
    } catch (Throwable) {
    }
}

function driverIsMysql(PDO $pdo): bool
{
    return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
}
