<?php

declare(strict_types=1);

namespace Domain\Entities;

final class DropTransaction
{
    public function __construct(
        public readonly int $id,
        public readonly int $studentId,
        public readonly int $teacherId,
        public readonly int $amount,
        public readonly string $type,
        public readonly ?string $reason,
        public readonly string $createdAt,
    ) {
    }
}

