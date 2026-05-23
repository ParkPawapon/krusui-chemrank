<?php
/** @var \Domain\Rank\RankProgress $progress */
$label = $label ?? 'หลอดทดลองหยดสาร';
$height = $height ?? 'large';
$tubeClass = $height === 'small' ? 'test-tube test-tube-small' : 'test-tube';
?>
<div class="<?= e($tubeClass) ?>" style="--fill-percent: <?= e((string) $progress->percentToNext) ?>%; --liquid-color: <?= e($progress->current->themeColor) ?>" role="img" aria-label="<?= e($label . ' เติมแล้ว ' . $progress->percentToNext . '%') ?>">
    <div class="tube-cap"></div>
    <div class="tube-glass">
        <div class="tube-bubbles" aria-hidden="true"></div>
        <div class="tube-liquid" aria-hidden="true"></div>
    </div>
    <div class="tube-base"></div>
</div>

