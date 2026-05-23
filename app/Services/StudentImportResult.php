<?php

declare(strict_types=1);

namespace App\Services;

final class StudentImportResult
{
    /**
     * @param array<int,string> $errors
     */
    public function __construct(
        public readonly int $totalRows,
        public readonly int $createdRows,
        public readonly int $failedRows,
        public readonly array $errors,
        public readonly bool $hasMoreErrors = false,
    ) {
    }
}
