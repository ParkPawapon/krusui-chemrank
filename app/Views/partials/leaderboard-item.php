<?php
/** @var array<string,mixed> $entry */
$student = $entry['student'];
$rank = $entry['rank'];
$progress = $entry['progress'];
$place = (int) $entry['place'];
$medalClass = $place === 1 ? 'gold' : ($place === 2 ? 'silver' : ($place === 3 ? 'bronze' : 'soft'));
$isTied = !empty($entry['is_tied']);
?>
<li class="leaderboard-row <?= e($place <= 3 ? 'top-three' : '') ?> <?= $isTied ? 'is-tied' : '' ?>" style="--rank-color: <?= e($rank->themeColor) ?>">
    <span class="leaderboard-place <?= e($medalClass) ?>">
        <small>อันดับ</small>
        <?= e((string) $place) ?>
    </span>
    <span class="leaderboard-row-icon">
        <?= \App\Support\View::partial('partials/rank-badge', ['rank' => $rank, 'size' => 'sm', 'showName' => false]) ?>
    </span>
    <span class="leaderboard-person">
        <strong><?= e($student->fullName) ?></strong>
        <small><?= e($student->className) ?> · <?= e($rank->thaiName) ?></small>
        <?php if ($isTied): ?>
            <em>อันดับร่วม</em>
        <?php endif; ?>
    </span>
    <span class="leaderboard-row-progress" aria-label="<?= e($progress->message) ?>">
        <span style="width: <?= e((string) $progress->percentToNext) ?>%"></span>
    </span>
    <span class="leaderboard-drops"><?= e((string) $student->drops) ?> <small>หยดสาร</small></span>
</li>
