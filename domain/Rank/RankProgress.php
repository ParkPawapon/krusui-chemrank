<?php

declare(strict_types=1);

namespace Domain\Rank;

final class RankProgress
{
    public function __construct(
        public readonly RankDefinition $current,
        public readonly ?RankDefinition $next,
        public readonly int $drops,
        public readonly int $dropsIntoRank,
        public readonly int $dropsNeededForNext,
        public readonly int $percentToNext,
        public readonly string $message,
    ) {
    }
}

