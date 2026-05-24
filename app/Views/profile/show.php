<?php
/** @var \Domain\Entities\User $user */
/** @var \App\Services\CsrfTokenManager $csrf */
/** @var string $profileLabel */
/** @var string $profileDescription */
/** @var string $profileVariant */
/** @var string $formAction */
/** @var string $buttonClass */
$isTeacherProfile = $profileVariant === 'teacher';
$eyebrow = $isTeacherProfile ? 'ตั้งค่าบัญชี' : $profileLabel;
$heading = $isTeacherProfile ? $profileLabel : $user->name;
$formNote = $isTeacherProfile ? 'บันทึกแล้วใช้รหัสผ่านใหม่ในการเข้าสู่ระบบครั้งถัดไป' : 'ใช้รหัสผ่านใหม่อย่างน้อย 8 ตัวอักษร';
?>
<section class="profile-page profile-page-<?= e($profileVariant) ?> page-band">
    <div class="profile-shell">
        <span class="profile-soft-drop profile-soft-drop-a" aria-hidden="true"></span>
        <span class="profile-soft-drop profile-soft-drop-b" aria-hidden="true"></span>

        <div class="profile-copy">
            <span class="profile-eyebrow"><?= e($eyebrow) ?></span>
            <h1><?= e($heading) ?></h1>
            <p><?= e($profileDescription) ?></p>
            <?php if ($isTeacherProfile): ?>
                <div class="profile-copy-badges" aria-label="รายละเอียดบัญชีครู">
                    <span>ข้อมูลนักเรียนยังอยู่ครบ</span>
                    <span>บันทึกหยดสารไม่เปลี่ยน</span>
                </div>
            <?php endif; ?>
        </div>

        <form action="<?= e(url($formAction)) ?>" method="post" class="profile-password-form">
            <?= $csrf->field() ?>
            <div class="profile-form-head">
                <span class="profile-lock-icon" aria-hidden="true"></span>
                <div>
                    <h2>เปลี่ยนรหัสผ่าน</h2>
                    <p><?= e($formNote) ?></p>
                </div>
            </div>

            <label>
                <span>รหัสผ่านเดิม</span>
                <input name="current_password" type="password" autocomplete="current-password" required placeholder="กรอกรหัสผ่านเดิม">
            </label>

            <label>
                <span>รหัสผ่านใหม่</span>
                <input name="new_password" type="password" autocomplete="new-password" required minlength="8" placeholder="อย่างน้อย 8 ตัวอักษร">
            </label>

            <label>
                <span>ยืนยันรหัสผ่านใหม่</span>
                <input name="new_password_confirmation" type="password" autocomplete="new-password" required minlength="8" placeholder="กรอกรหัสผ่านใหม่อีกครั้ง">
            </label>

            <button class="btn <?= e($buttonClass) ?> profile-submit" type="submit">บันทึกรหัสผ่านใหม่</button>
        </form>
    </div>
</section>
