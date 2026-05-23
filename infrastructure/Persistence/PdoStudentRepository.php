<?php

declare(strict_types=1);

namespace Infrastructure\Persistence;

use Domain\Entities\Student;
use Domain\Repositories\StudentRepository;
use PDO;

final class PdoStudentRepository implements StudentRepository
{
    private ?bool $hasLegacyClassNameColumn = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?Student
    {
        $statement = $this->pdo->prepare($this->baseSelect() . ' WHERE s.id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByUserId(int $userId): ?Student
    {
        $statement = $this->pdo->prepare($this->baseSelect() . ' WHERE s.user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $row = $statement->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function all(?string $className = null, ?string $search = null): array
    {
        $sql = $this->baseSelect() . ' WHERE 1 = 1';
        $params = [];

        if ($className !== null && trim($className) !== '') {
            [$classLevel, $room] = $this->splitClassName($className);

            if ($room !== '') {
                $sql .= ' AND s.`class` = :class_level AND s.room = :room';
                $params['class_level'] = $classLevel;
                $params['room'] = $room;
            } else {
                $sql .= ' AND s.`class` = :class_level';
                $params['class_level'] = $classLevel;
            }
        }

        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (s.full_name LIKE :search OR s.student_number LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        $sql .= ' ORDER BY s.`class` ASC, s.room ASC, s.drops DESC, s.full_name ASC';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return array_map(fn (array $row): Student => $this->hydrate($row), $statement->fetchAll());
    }

    public function classNames(): array
    {
        $statement = $this->pdo->query('SELECT DISTINCT `class`, room FROM students ORDER BY `class` ASC, room ASC');
        $classNames = [];

        foreach ($statement->fetchAll() as $row) {
            $classNames[] = Student::formatClassName((string) $row['class'], (string) $row['room']);
        }

        return array_values(array_filter($classNames));
    }

    public function create(
        string $fullName,
        string $studentNumber,
        string $classLevel,
        string $room,
        int $academicYearId,
        ?int $userId,
        int $drops = 0,
    ): Student {
        $columns = ['user_id', 'student_number', 'full_name', '`class`', 'room', 'academic_year_id', 'drops', 'created_at', 'updated_at'];
        $values = [':user_id', ':student_number', ':full_name', ':class_level', ':room', ':academic_year_id', ':drops', 'CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP'];
        $params = [
            'user_id' => $userId,
            'student_number' => trim($studentNumber),
            'full_name' => trim($fullName),
            'class_level' => trim($classLevel),
            'room' => trim($room),
            'academic_year_id' => $academicYearId,
            'drops' => max(0, $drops),
        ];

        if ($this->hasLegacyClassNameColumn()) {
            $columns[] = 'class_name';
            $values[] = ':class_name';
            $params['class_name'] = Student::formatClassName($params['class_level'], $params['room']);
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO students (' . implode(', ', $columns) . ')
             VALUES (' . implode(', ', $values) . ')'
        );
        $statement->execute($params);

        return $this->findById((int) $this->pdo->lastInsertId())
            ?? throw new \RuntimeException('Unable to create student.');
    }

    public function studentNumberExists(string $studentNumber): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM students WHERE student_number = :student_number');
        $statement->execute(['student_number' => trim($studentNumber)]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function updateDrops(int $studentId, int $drops): Student
    {
        $statement = $this->pdo->prepare(
            'UPDATE students SET drops = :drops, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute([
            'drops' => max(0, $drops),
            'id' => $studentId,
        ]);

        return $this->findById($studentId)
            ?? throw new \RuntimeException('Student not found after update.');
    }

    public function delete(int $studentId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM students WHERE id = :id');
        $statement->execute(['id' => $studentId]);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function hydrate(array $row): Student
    {
        return new Student(
            (int) $row['id'],
            $row['user_id'] !== null ? (int) $row['user_id'] : null,
            (string) $row['student_number'],
            (string) $row['full_name'],
            (string) $row['class'],
            (string) $row['room'],
            (int) $row['academic_year_id'],
            (string) ($row['academic_year_name'] ?? ''),
            (int) $row['drops'],
            (string) $row['created_at'],
            (string) $row['updated_at'],
        );
    }

    private function baseSelect(): string
    {
        return 'SELECT s.*, ay.name AS academic_year_name
            FROM students s
            LEFT JOIN academic_years ay ON ay.id = s.academic_year_id';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitClassName(string $className): array
    {
        $className = trim($className);

        if (!str_contains($className, '/')) {
            return [$className, ''];
        }

        [$classLevel, $room] = explode('/', $className, 2);

        return [trim($classLevel), trim($room)];
    }

    private function hasLegacyClassNameColumn(): bool
    {
        if ($this->hasLegacyClassNameColumn !== null) {
            return $this->hasLegacyClassNameColumn;
        }

        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $statement = $this->pdo->query('PRAGMA table_info(students)');
            $columns = array_map(static fn (array $row): string => (string) $row['name'], $statement->fetchAll());

            return $this->hasLegacyClassNameColumn = in_array('class_name', $columns, true);
        }

        $statement = $this->pdo->query(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'students'
               AND COLUMN_NAME = 'class_name'"
        );

        return $this->hasLegacyClassNameColumn = (int) $statement->fetchColumn() > 0;
    }
}
