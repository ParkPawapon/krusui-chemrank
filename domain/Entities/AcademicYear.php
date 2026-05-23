<?php

declare(strict_types=1);

namespace Domain\Entities;

final class AcademicYear
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly bool $isActive,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }
}
