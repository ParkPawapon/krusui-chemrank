<?php
/** @var \Domain\Entities\Student $student */
/** @var \Domain\Rank\RankDefinition $rank */
/** @var \Domain\Rank\RankProgress $progress */
$nextRank = $progress->next;
$nextTitle = $nextRank?->thaiName ?? 'ระดับสูงสุด';
$nextDescription = $nextRank?->description ?? 'รักษาความสม่ำเสมอและเก็บหยดสารต่อได้เรื่อย ๆ';
$nextDropsText = $nextRank
    ? 'อีก ' . $progress->dropsNeededForNext . ' หยด'
    : 'ครบแล้ว';
$progressLabel = $nextRank
    ? 'ไปขั้นต่อไป'
    : 'สถานะตอนนี้';
?>
<section class="student-minimal page-band" style="--rank-color: <?= e($rank->themeColor) ?>">
    <div class="student-minimal-shell">
        <header class="student-minimal-header">
            <div>
                <span class="student-minimal-eyebrow">ห้องทดลองของฉัน</span>
                <h1><?= e($student->fullName) ?></h1>
            </div>
            <div class="student-minimal-meta" aria-label="ข้อมูลนักเรียน">
                <span><?= e($student->className) ?></span>
                <span><?= e($student->studentNumber) ?></span>
            </div>
        </header>

        <div class="student-minimal-grid">
            <article class="student-level-card current-level-card">
                <span class="student-card-kicker">ระดับล่าสุด</span>
                <div class="student-level-main">
                    <div class="student-level-icon" aria-hidden="true">
                        <img class="pixel-art" src="<?= e($rank->assetPath) ?>" width="160" height="160" alt="" decoding="async">
                    </div>
                    <div>
                        <h2><?= e($rank->thaiName) ?></h2>
                        <p><?= e($rank->description) ?></p>
                    </div>
                </div>
                <div class="student-drops-pill" aria-label="หยดสารสะสม">
                    <strong><?= e((string) $student->drops) ?></strong>
                    <span>หยดสาร</span>
                </div>
            </article>

            <article class="student-level-card next-level-card">
                <div class="student-next-head">
                    <span class="student-card-kicker">ขั้นต่อไป</span>
                    <strong><?= e($nextDropsText) ?></strong>
                </div>

                <div class="student-level-main">
                    <div class="student-level-icon student-level-icon-next" aria-hidden="true">
                        <img class="pixel-art" src="<?= e(($nextRank ?? $rank)->assetPath) ?>" width="140" height="140" alt="" decoding="async">
                    </div>
                    <div>
                        <h2><?= e($nextTitle) ?></h2>
                        <p><?= e($nextDescription) ?></p>
                    </div>
                </div>

                <div class="student-minimal-progress">
                    <div class="student-progress-head">
                        <span><?= e($progressLabel) ?></span>
                        <strong><?= e((string) $progress->percentToNext) ?>%</strong>
                    </div>
                    <div class="wide-progress student-wide-progress" aria-label="<?= e($progress->message) ?>">
                        <span style="width: <?= e((string) $progress->percentToNext) ?>%"></span>
                    </div>
                    <p><?= e($progress->message) ?></p>
                </div>
            </article>
        </div>
    </div>
</section>
