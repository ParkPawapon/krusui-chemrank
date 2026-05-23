<?php

declare(strict_types=1);

namespace App\Services;

use Domain\Entities\Student;
use Domain\Rank\RankDefinition;
use Domain\Rank\RankProgress;

final class DropAdjustmentResult
{
    public function __construct(
        public readonly Student $student,
        public readonly int $previousDrops,
        public readonly int $newDrops,
        public readonly RankDefinition $previousRank,
        public readonly RankDefinition $newRank,
        public readonly RankProgress $progress,
    ) {
    }

    public function rankChanged(): bool
    {
        return $this->previousRank->level !== $this->newRank->level;
    }

    public function direction(): string
    {
        return $this->newRank->level > $this->previousRank->level ? 'up' : ($this->newRank->level < $this->previousRank->level ? 'down' : 'same');
    }
}

