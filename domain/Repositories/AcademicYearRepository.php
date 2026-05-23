<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\AcademicYear;

interface AcademicYearRepository
{
    /**
     * @return array<int,AcademicYear>
     */
    public function all(): array;

    public function findById(int $id): ?AcademicYear;

    public function active(): ?AcademicYear;

    public function activeOrCreateDefault(): AcademicYear;
}
