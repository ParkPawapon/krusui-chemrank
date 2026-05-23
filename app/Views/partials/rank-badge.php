<?php
/** @var \Domain\Rank\RankDefinition $rank */
$size = $size ?? 'md';
$showName = $showName ?? true;
$classes = [
    'sm' => 'rank-badge rank-badge-sm',
    'md' => 'rank-badge',
    'lg' => 'rank-badge rank-badge-lg',
][$size] ?? 'rank-badge';
?>
<span class="<?= e($classes) ?>" style="--rank-color: <?= e($rank->themeColor) ?>">
    <img class="pixel-art" src="<?= e($rank->assetPath) ?>" width="96" height="96" alt="" decoding="async">
    <?php if ($showName): ?>
        <span>
            <strong><?= e($rank->thaiName) ?></strong>
            <small><?= e($rank->englishSlug) ?></small>
        </span>
    <?php endif; ?>
</span>
