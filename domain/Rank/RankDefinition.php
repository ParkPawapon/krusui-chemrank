<?php

declare(strict_types=1);

namespace Domain\Rank;

final class RankDefinition
{
    public function __construct(
        public readonly int $level,
        public readonly int $minDrops,
        public readonly ?int $maxDrops,
        public readonly string $thaiName,
        public readonly string $englishSlug,
        public readonly string $description,
        public readonly string $assetPath,
        public readonly string $themeColor,
    ) {
    }

    public function contains(int $drops): bool
    {
        return $drops >= $this->minDrops && ($this->maxDrops === null || $drops <= $this->maxDrops);
    }

    public function rangeLabel(): string
    {
        return $this->maxDrops === null
            ? $this->minDrops . '+'
            : $this->minDrops . '-' . $this->maxDrops;
    }
}

