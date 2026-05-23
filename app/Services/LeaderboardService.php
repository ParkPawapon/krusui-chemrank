<?php

declare(strict_types=1);

namespace App\Services;

use Domain\Repositories\StudentRepository;
use Domain\Rank\RankRegistry;

final class LeaderboardService
{
    public function __construct(
        private readonly StudentRepository $students,
        private readonly RankRegistry $ranks,
    ) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function ranked(?string $className = null): array
    {
        $students = $this->students->all($className);
        usort($students, static fn ($a, $b): int => [$b->drops, $a->fullName] <=> [$a->drops, $b->fullName]);

        $ranked = [];
        $position = 0;
        $displayRank = 0;
        $previousDrops = null;

        foreach ($students as $student) {
            $position++;

            if ($previousDrops === null || $student->drops !== $previousDrops) {
                $displayRank = $position;
                $previousDrops = $student->drops;
            }

            $ranked[] = [
                'place' => $displayRank,
                'student' => $student,
                'rank' => $this->ranks->forDrops($student->drops),
                'progress' => $this->ranks->progress($student->drops),
                'is_tied' => $position !== $displayRank,
            ];
        }

        return $ranked;
    }
}

