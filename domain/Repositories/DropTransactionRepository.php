<?php

declare(strict_types=1);

namespace Domain\Repositories;

interface DropTransactionRepository
{
    public function create(int $studentId, int $teacherId, int $amount, string $type, ?string $reason): void;
}

