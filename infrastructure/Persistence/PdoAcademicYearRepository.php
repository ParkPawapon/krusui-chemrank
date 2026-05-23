<?php

declare(strict_types=1);

namespace Infrastructure\Persistence;

use Domain\Entities\AcademicYear;
use Domain\Repositories\AcademicYearRepository;
use PDO;

final class PdoAcademicYearRepository implements AcademicYearRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $statement = $this->pdo->query('SELECT * FROM academic_years ORDER BY is_active DESC, name DESC');

        return array_map(fn (array $row): AcademicYear => $this->hydrate($row), $statement->fetchAll());
    }

    public function findById(int $id): ?AcademicYear
    {
        $statement = $this->pdo->prepare('SELECT * FROM academic_years WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function active(): ?AcademicYear
    {
        $statement = $this->pdo->query('SELECT * FROM academic_years WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
        $row = $statement->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function activeOrCreateDefault(): AcademicYear
    {
        $active = $this->active();

        if ($active) {
            return $active;
        }

        $name = (string) ((int) date('Y') + 543);
        $statement = $this->pdo->prepare(
            'INSERT INTO academic_years (name, is_active, created_at, updated_at)
             VALUES (:name, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $statement->execute(['name' => $name]);

        return $this->findById((int) $this->pdo->lastInsertId())
            ?? throw new \RuntimeException('Unable to create academic year.');
    }

    /**
     * @param array<string,mixed> $row
     */
    private function hydrate(array $row): AcademicYear
    {
        return new AcademicYear(
            (int) $row['id'],
            (string) $row['name'],
            (bool) $row['is_active'],
            (string) $row['created_at'],
            (string) $row['updated_at'],
        );
    }
}
