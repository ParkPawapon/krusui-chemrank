<?php
/** @var array<int,\Domain\Rank\RankDefinition> $ranks */
$totalRanks = count($ranks);
$firstRank = $ranks[0] ?? null;
$lastRank = $ranks[array_key_last($ranks)] ?? null;
?>
<section class="rank-guide-page page-band">
    <div class="rank-guide-hero">
        <div class="rank-guide-copy">
            <h1>แผนที่ Rank ของหยดสาร</h1>
            <p>ดูแต่ละระดับของหยดสาร ตั้งแต่เริ่มสะสมไปจนถึงขั้นสูงสุด พร้อมช่วงหยดที่ต้องใช้ในแต่ละระดับ</p>
            <div class="rank-guide-facts" aria-label="ภาพรวม rank">
                <span><strong><?= e((string) $totalRanks) ?></strong> ระดับ</span>
                <span><strong><?= e($firstRank?->rangeLabel() ?? '0') ?></strong> เริ่มต้น</span>
                <span><strong><?= e($lastRank?->rangeLabel() ?? '136+') ?></strong> สะสมต่อได้</span>
            </div>
        </div>

        <div class="rank-guide-lab" aria-hidden="true">
            <span class="guide-spark guide-spark-a"></span>
            <span class="guide-spark guide-spark-b"></span>
            <span class="guide-bubble guide-bubble-a"></span>
            <span class="guide-bubble guide-bubble-b"></span>
            <div class="guide-lab-glass">
                <?php if ($firstRank): ?>
                    <img class="pixel-art guide-lab-rank guide-lab-rank-start" src="<?= e($firstRank->assetPath) ?>" width="96" height="96" alt="" decoding="async">
                <?php endif; ?>
                <span class="guide-lab-path"></span>
                <?php if ($lastRank): ?>
                    <img class="pixel-art guide-lab-rank guide-lab-rank-finish" src="<?= e($lastRank->assetPath) ?>" width="96" height="96" alt="" decoding="async">
                <?php endif; ?>
                <span class="guide-mini-tube guide-mini-tube-a"></span>
                <span class="guide-mini-tube guide-mini-tube-b"></span>
                <span class="guide-mini-flask"></span>
            </div>
        </div>
    </div>

    <div class="rank-guide-track" aria-label="เส้นทางวิวัฒนาการ rank">
        <?php foreach ($ranks as $index => $rank): ?>
            <article class="rank-guide-card" style="--rank-color: <?= e($rank->themeColor) ?>">
                <div class="rank-guide-card-top">
                    <span>ระดับ <?= e((string) $rank->level) ?></span>
                    <strong><?= e($rank->rangeLabel()) ?> หยด</strong>
                </div>
                <div class="rank-guide-icon">
                    <img class="pixel-art" src="<?= e($rank->assetPath) ?>" width="112" height="112" alt="" loading="lazy" decoding="async">
                </div>
                <div class="rank-guide-card-body">
                    <h2><?= e($rank->thaiName) ?></h2>
                    <p><?= e($rank->description) ?></p>
                </div>
                <?php if ($index + 1 < $totalRanks): ?>
                    <span class="rank-guide-next" aria-hidden="true"></span>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
