<?php
/** @var array<int,\Domain\Rank\RankDefinition> $ranks */
?>
<section class="landing-hero page-band">
    <div class="hero-copy">
        <div class="hero-game-logo">
            <img class="hero-brand-logo" src="<?= e(asset('brand/chem-rank.svg')) ?>" alt="โลโก้ระบบเก็บหยดสาร" loading="eager" decoding="async">
        </div>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= e(url('/login?role=student')) ?>">นักเรียนเข้าสู่ระบบ</a>
            <a class="btn btn-pink" href="<?= e(url('/login?role=teacher')) ?>">ครูเข้าสู่ระบบ</a>
        </div>
    </div>

    <div class="landing-stats" aria-label="ภาพรวมระบบ">
        <article class="stat-card mint-card">
            <span class="stat-icon flask-icon" aria-hidden="true"></span>
            <div><strong>7 ระดับ</strong><small>ค่อย ๆ โตตามหยดสาร</small></div>
        </article>
        <article class="stat-card purple-card">
            <span class="stat-icon drop-icon" aria-hidden="true"></span>
            <div><strong>หยดสาร</strong><small>คะแนนที่ดูเป็นมิตรกว่าเดิม</small></div>
        </article>
        <article class="stat-card peach-card">
            <span class="stat-icon sparkle-icon" aria-hidden="true"></span>
            <div><strong>ครูจัดการง่าย</strong><small>เพิ่ม ลด และดูบันทึกได้เร็ว</small></div>
        </article>
    </div>

    <div class="rank-preview" aria-label="ตัวอย่าง rank ทั้งหมด">
        <?php foreach ($ranks as $rank): ?>
            <a class="rank-preview-item" href="<?= e(url('/ranks')) ?>" style="--rank-color: <?= e($rank->themeColor) ?>">
                <span class="rank-card-label">ระดับ <?= e((string) $rank->level) ?></span>
                <img class="pixel-art" src="<?= e($rank->assetPath) ?>" width="96" height="96" alt="" decoding="async">
                <span><?= e($rank->thaiName) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
