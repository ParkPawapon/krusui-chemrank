<?php

declare(strict_types=1);

namespace Domain\Rank;

final class RankRegistry
{
    /**
     * @return array<int,RankDefinition>
     */
    public function all(): array
    {
        static $ranks = null;

        if ($ranks === null) {
            $ranks = [
                new RankDefinition(1, 0, 10, 'ละอองแรก', 'First Drop', 'เริ่มต้นก้าวแรกของการทดลอง', '/assets/ranks/rank-01-first-drop.webp', '#74b7ff'),
                new RankDefinition(2, 11, 25, 'หยดต้นกำเนิด', 'Origin Drop', 'เริ่มสะสมและสร้างพื้นฐาน', '/assets/ranks/rank-02-origin-drop.webp', '#69d7c6'),
                new RankDefinition(3, 26, 45, 'สารผสมเริ่มต้น', 'Early Mixture', 'เริ่มเกิดการรวมตัวขององค์ประกอบ', '/assets/ranks/rank-03-early-mixture.webp', '#ff9e8f'),
                new RankDefinition(4, 46, 70, 'สารละลายก่อตัว', 'Forming Solution', 'เริ่มมีความสมบูรณ์และเสถียร', '/assets/ranks/rank-04-forming-solution.webp', '#69d7c6'),
                new RankDefinition(5, 71, 100, 'สารเข้มข้น', 'Concentrated Force', 'พลังและความสามารถเพิ่มขึ้น', '/assets/ranks/rank-05-concentrated-force.webp', '#9b78f2'),
                new RankDefinition(6, 101, 135, 'ภาวะอิ่มตัว', 'Saturated State', 'ถึงระดับสูงที่ต้องใช้ความพยายามมากขึ้น', '/assets/ranks/rank-06-saturated-state.webp', '#ffc75d'),
                new RankDefinition(7, 136, null, 'เหนือจุดอิ่มตัว', 'Beyond Saturation', 'ผู้เชี่ยวชาญแห่งห้องทดลอง', '/assets/ranks/rank-07-beyond-saturation.webp', '#ef7192'),
            ];
        }

        return $ranks;
    }

    public function forDrops(int $drops): RankDefinition
    {
        $drops = max(0, $drops);

        foreach ($this->all() as $rank) {
            if ($rank->contains($drops)) {
                return $rank;
            }
        }

        return $this->all()[count($this->all()) - 1];
    }

    public function nextAfter(RankDefinition $current): ?RankDefinition
    {
        foreach ($this->all() as $rank) {
            if ($rank->level === $current->level + 1) {
                return $rank;
            }
        }

        return null;
    }

    public function progress(int $drops): RankProgress
    {
        $drops = max(0, $drops);
        $current = $this->forDrops($drops);
        $next = $this->nextAfter($current);

        if ($next === null) {
            return new RankProgress(
                $current,
                null,
                $drops,
                max(0, $drops - $current->minDrops),
                0,
                100,
                'รักษาพลังนักเคมีระดับสูงต่อไป'
            );
        }

        $rankSpan = max(1, $next->minDrops - $current->minDrops);
        $dropsIntoRank = max(0, $drops - $current->minDrops);
        $needed = max(0, $next->minDrops - $drops);
        $percent = (int) min(100, max(0, round(($dropsIntoRank / $rankSpan) * 100)));

        return new RankProgress(
            $current,
            $next,
            $drops,
            $dropsIntoRank,
            $needed,
            $percent,
            'อีก ' . $needed . ' หยดจะวิวัฒน์เป็น ' . $next->thaiName
        );
    }
}
