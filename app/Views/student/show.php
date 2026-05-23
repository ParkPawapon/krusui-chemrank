<?php
/** @var \Domain\Entities\Student $student */
/** @var \Domain\Rank\RankDefinition $rank */
/** @var \Domain\Rank\RankProgress $progress */
$nextRank = $progress->next;
$nextTitle = $nextRank?->thaiName ?? 'ระดับสูงสุด';
$nextDescription = $nextRank?->description ?? 'รักษาความสม่ำเสมอและเก็บหยดสารต่อได้เรื่อย ๆ';
$nextDropsText = $nextRank
    ? 'อีก ' . $progress->dropsNeededForNext . ' หยด'
    : 'ครบระดับสูงสุดแล้ว';
$progressLabel = $nextRank
    ? 'ความคืบหน้าไปขั้นต่อไป'
    : 'ระดับสูงสุด';
?>
<section class="student-minimal page-band" style="--rank-color: <?= e($rank->themeColor) ?>">
    <div class="student-minimal-shell">
        <span class="student-soft-dot student-soft-dot-a" aria-hidden="true"></span>
        <span class="student-soft-dot student-soft-dot-b" aria-hidden="true"></span>

        <header class="student-minimal-header">
            <div class="student-header-copy">
                <span class="student-minimal-eyebrow">ห้องทดลองของฉัน</span>
                <h1><?= e($student->fullName) ?></h1>
                <div class="student-minimal-meta" aria-label="ข้อมูลนักเรียน">
                    <span>ห้องเรียน <?= e($student->className) ?></span>
                    <span>เลขประจำตัว <?= e($student->studentNumber) ?></span>
                </div>
            </div>

            <div class="student-drops-pill" aria-label="หยดสารสะสม">
                <span>หยดสารสะสม</span>
                <strong><?= e((string) $student->drops) ?></strong>
            </div>
        </header>

        <div class="student-rank-stage">
            <article class="student-current-rank">
                <div class="student-level-icon student-current-icon" aria-hidden="true">
                    <img class="pixel-art" src="<?= e($rank->assetPath) ?>" width="180" height="180" alt="" decoding="async">
                </div>

                <div class="student-current-copy">
                    <span class="student-card-kicker">ระดับล่าสุด</span>
                    <h2><?= e($rank->thaiName) ?></h2>
                    <p><?= e($rank->description) ?></p>
                </div>
            </article>

            <aside class="student-next-rank-card">
                <div class="student-next-head">
                    <span class="student-card-kicker">ขั้นต่อไป</span>
                    <strong><?= e($nextDropsText) ?></strong>
                </div>

                <div class="student-next-preview">
                    <div class="student-next-icon" aria-hidden="true">
                        <img class="pixel-art" src="<?= e(($nextRank ?? $rank)->assetPath) ?>" width="110" height="110" alt="" decoding="async">
                    </div>
                    <div>
                        <h3><?= e($nextTitle) ?></h3>
                        <p><?= e($nextDescription) ?></p>
                    </div>
                </div>
            </aside>
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
    </div>
</section>
