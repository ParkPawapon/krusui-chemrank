<?php
/** @var \Domain\Entities\User $user */
/** @var \App\Services\CsrfTokenManager $csrf */
?>
<section class="profile-page page-band">
    <div class="profile-shell">
        <span class="profile-soft-drop profile-soft-drop-a" aria-hidden="true"></span>
        <span class="profile-soft-drop profile-soft-drop-b" aria-hidden="true"></span>

        <div class="profile-copy">
            <span class="profile-eyebrow">โปรไฟล์นักเรียน</span>
            <h1><?= e($user->name) ?></h1>
            <p>เปลี่ยนรหัสผ่านของบัญชีให้ปลอดภัยขึ้น โดยข้อมูลหยดสารและ Rank จะไม่เปลี่ยนแปลง</p>
        </div>

        <form action="<?= e(url('/student/profile/password')) ?>" method="post" class="profile-password-form">
            <?= $csrf->field() ?>
            <div class="profile-form-head">
                <span class="profile-lock-icon" aria-hidden="true"></span>
                <div>
                    <h2>เปลี่ยนรหัสผ่าน</h2>
                    <p>ใช้รหัสผ่านใหม่อย่างน้อย 8 ตัวอักษร</p>
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

            <button class="btn btn-primary profile-submit" type="submit">บันทึกรหัสผ่านใหม่</button>
        </form>
    </div>
</section>
