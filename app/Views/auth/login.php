<?php
/** @var string $role */
/** @var \App\Services\CsrfTokenManager $csrf */
/** @var \Domain\Rank\RankDefinition $loginRank */
$isTeacher = $role === 'teacher';
$roleLabel = $isTeacher ? 'Teacher Lab' : 'Student Lab';
$heading = $isTeacher ? 'ครูเข้าสู่ระบบ' : 'นักเรียนเข้าสู่ระบบ';
$lead = $isTeacher
    ? 'จัดการหยดสารของนักเรียนอย่างรวดเร็ว พร้อมบันทึกการปรับคะแนนทุกครั้ง'
    : 'กลับเข้าสู่ห้องทดลอง ดูหยดสาร และติดตาม rank ถัดไปของตัวเอง';
$rankName = $loginRank->englishSlug;
$rankCaption = $isTeacher ? 'ดูแลห้องทดลอง' : 'เริ่มเก็บหยดสาร';
$identifierLabel = $isTeacher ? 'ชื่อผู้ใช้' : 'เลขประจำตัวนักเรียน';
$identifierPlaceholder = $isTeacher ? 'ชื่อผู้ใช้' : 'เช่น 12345';
?>
<section class="auth-page page-band <?= e($isTeacher ? 'auth-page-teacher' : 'auth-page-student') ?>">
    <div class="auth-shell">
        <div class="auth-visual" aria-hidden="true">
            <span class="auth-floating-drop drop-a"></span>
            <span class="auth-floating-drop drop-b"></span>
            <span class="auth-floating-spark spark-a"></span>
            <span class="auth-floating-spark spark-b"></span>

            <div class="auth-visual-card">
                <div class="auth-pixel-lab">
                    <span class="auth-pixel-shelf"></span>
                    <span class="auth-testtube auth-testtube-left"></span>
                    <span class="auth-testtube auth-testtube-main"></span>
                    <span class="auth-flask"></span>
                </div>
                <div class="auth-rank-card">
                    <img class="pixel-art" src="<?= e($loginRank->assetPath) ?>" width="96" height="96" alt="" decoding="async">
                    <span>
                        <strong><?= e($rankName) ?></strong>
                        <small><?= e($rankCaption) ?></small>
                    </span>
                </div>
            </div>
        </div>

        <div class="auth-panel">
            <div class="auth-panel-header">
                <span class="<?= e($isTeacher ? 'teacher-login-icon' : 'student-login-icon') ?>" aria-hidden="true"></span>
                <div>
                    <small><?= e($roleLabel) ?></small>
                    <h1><?= e($heading) ?></h1>
                </div>
            </div>

            <p class="auth-lead"><?= e($lead) ?></p>

            <form action="<?= e(url('/login')) ?>" method="post" class="form-stack auth-form">
                <?= $csrf->field() ?>
                <input type="hidden" name="role" value="<?= e($role) ?>">
                <label>
                    <span><?= e($identifierLabel) ?></span>
                    <?php if ($isTeacher): ?>
                        <input name="login_identifier" type="text" autocomplete="username" required maxlength="60" autofocus placeholder="<?= e($identifierPlaceholder) ?>">
                    <?php else: ?>
                        <input name="login_identifier" type="text" autocomplete="username" required inputmode="numeric" pattern="\d{5}" minlength="5" maxlength="5" autofocus placeholder="<?= e($identifierPlaceholder) ?>">
                    <?php endif; ?>
                </label>
                <label>
                    <span>รหัสผ่าน</span>
                    <input name="password" type="password" autocomplete="current-password" required placeholder="รหัสผ่าน">
                </label>
                <button class="btn <?= $isTeacher ? 'btn-pink' : 'btn-primary' ?> auth-submit" type="submit">
                    <span>เข้าสู่ระบบ</span>
                    <span class="auth-submit-drop" aria-hidden="true"></span>
                </button>
            </form>
        </div>
    </div>
</section>
