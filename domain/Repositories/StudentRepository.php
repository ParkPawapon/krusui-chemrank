<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\Student;

interface StudentRepository
{
    public function findById(int $id): ?Student;

    public function findByUserId(int $userId): ?Student;

    /**
     * @return array<int,Student>
     */
    public function all(?string $className = null, ?string $search = null): array;

    /**
     * @return array<int,string>
     */
    public function classNames(): array;

    public function create(
        string $fullName,
        string $studentNumber,
        string $classLevel,
        string $room,
        int $academicYearId,
        ?int $userId,
        int $drops = 0,
    ): Student;

    public function studentNumberExists(string $studentNumber): bool;

    public function updateDrops(int $studentId, int $drops): Student;

    public function delete(int $studentId): void;
}
