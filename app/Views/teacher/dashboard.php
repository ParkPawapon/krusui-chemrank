<?php
/** @var array<int,\Domain\Entities\Student> $students */
/** @var array<int,string> $classNames */
/** @var array<int,\Domain\Entities\AcademicYear> $academicYears */
/** @var \Domain\Entities\AcademicYear $activeAcademicYear */
/** @var string $selectedClass */
/** @var string $search */
/** @var \Domain\Rank\RankRegistry $ranks */
/** @var \App\Services\CsrfTokenManager $csrf */
/** @var array<string,string>|null $passwordResetNotice */
$exportParams = [];

if ($search !== '') {
    $exportParams['q'] = $search;
}

if ($selectedClass !== '') {
    $exportParams['class'] = $selectedClass;
}

$exportUrl = url('/teacher/students/export') . ($exportParams !== [] ? '?' . http_build_query($exportParams) : '');
?>
<section class="teacher-dashboard page-band">
    <section class="teacher-hero">
        <div class="teacher-hero-copy">
            <h1>ห้องครู Chem Rank</h1>
            <p>เพิ่มรายชื่อ ปรับหยดสาร และดูภาพรวมของห้องเรียนได้ในหน้าเดียว โดยทุกการเปลี่ยนแปลงถูกบันทึกไว้ครบถ้วน</p>
        </div>
        <div class="teacher-hero-visual" aria-hidden="true">
            <span class="teacher-orbit-drop teacher-orbit-drop-a"></span>
            <span class="teacher-orbit-drop teacher-orbit-drop-b"></span>
            <div class="teacher-lab-board">
                <span class="teacher-lab-glow"></span>
                <span class="teacher-lab-shelf"></span>
                <span class="teacher-lab-flask"></span>
                <span class="teacher-lab-tube teacher-lab-tube-mint"></span>
                <span class="teacher-lab-tube teacher-lab-tube-rose"></span>
                <span class="teacher-lab-note"></span>
                <span class="teacher-lab-spark teacher-lab-spark-a"></span>
                <span class="teacher-lab-spark teacher-lab-spark-b"></span>
            </div>
        </div>
    </section>

    <div class="teacher-workspace-grid">
        <section class="teacher-panel teacher-add-panel">
            <span class="teacher-card-drop teacher-card-drop-a" aria-hidden="true"></span>
            <div class="teacher-panel-head">
                <div>
                    <h2>เพิ่มนักเรียน</h2>
                    <p>เพิ่มรายชื่อทีละคน พร้อมผูกปีการศึกษาและห้องเรียนให้เรียบร้อย</p>
                </div>
                <span class="teacher-panel-icon teacher-panel-icon-student" aria-hidden="true"></span>
            </div>
            <form action="<?= e(url('/teacher/students')) ?>" method="post" class="teacher-form-grid">
                <?= $csrf->field() ?>
                <label>
                    <span>ชื่อนักเรียน</span>
                    <input name="full_name" type="text" required maxlength="160" autocomplete="name" placeholder="เช่น นายทดสอบ เคมี">
                </label>
                <label>
                    <span>เลขประจำตัวนักเรียน</span>
                    <input name="student_number" type="text" required inputmode="numeric" pattern="\d{5}" minlength="5" maxlength="5" autocomplete="off" placeholder="เช่น 12345">
                </label>
                <label>
                    <span>ชั้น</span>
                    <input name="class_level" type="text" required inputmode="numeric" pattern="\d{1,2}" maxlength="2" autocomplete="off" placeholder="เช่น 4">
                </label>
                <label>
                    <span>ห้อง</span>
                    <input name="room" type="text" required inputmode="numeric" pattern="\d{1,2}" maxlength="2" autocomplete="off" placeholder="เช่น 1">
                </label>
                <label class="teacher-academic-field">
                    <span>ปีการศึกษา</span>
                    <select name="academic_year_id" required>
                        <?php foreach ($academicYears as $academicYear): ?>
                            <option value="<?= e((string) $academicYear->id) ?>" <?= $academicYear->id === $activeAcademicYear->id ? 'selected' : '' ?>>
                                <?= e($academicYear->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="btn btn-primary teacher-submit" type="submit">เพิ่มนักเรียน</button>
            </form>
        </section>

        <section id="teacher-import" class="teacher-panel teacher-import-panel">
            <span class="teacher-card-drop teacher-card-drop-b" aria-hidden="true"></span>
            <div class="teacher-panel-head">
                <div>
                    <h2>นำเข้ารายชื่อ Excel</h2>
                    <p>เลือกไฟล์ .xlsx แล้วให้ระบบเพิ่มรายชื่อเข้าห้องเรียนในครั้งเดียว</p>
                </div>
                <div class="teacher-panel-tools">
                    <span class="teacher-panel-icon teacher-panel-icon-upload" aria-hidden="true"></span>
                    <a class="teacher-template-download" href="<?= e(url('/teacher/students/import-template')) ?>">ดาวน์โหลดตัวอย่าง Excel</a>
                </div>
            </div>

            <form
                action="<?= e(url('/teacher/students/import')) ?>"
                method="post"
                enctype="multipart/form-data"
                class="teacher-import-form"
                data-import-form
                data-import-preview-url="<?= e(url('/teacher/students/import-preview')) ?>"
            >
                <?= $csrf->field() ?>
                <label class="teacher-upload-box">
                    <span>ไฟล์รายชื่อนักเรียน</span>
                    <input name="student_file" type="file" required accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" data-import-file>
                    <small>ขนาดไฟล์ไม่เกิน 5 MB</small>
                </label>
                <div class="teacher-import-bottom">
                    <label>
                        <span>ปีการศึกษา</span>
                        <select name="academic_year_id" required>
                            <?php foreach ($academicYears as $academicYear): ?>
                                <option value="<?= e((string) $academicYear->id) ?>" <?= $academicYear->id === $activeAcademicYear->id ? 'selected' : '' ?>>
                                    <?= e($academicYear->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </form>
        </section>
    </div>

    <section class="teacher-roster-area" data-teacher-results aria-live="polite">
        <div class="teacher-roster-top">
            <div class="teacher-roster-heading">
                <div class="teacher-table-title-block">
                    <h2>รายชื่อนักเรียน</h2>
                </div>
                <a class="teacher-export-button" href="<?= e($exportUrl) ?>" data-teacher-export-link>
                    <span class="teacher-export-icon" aria-hidden="true"></span>
                    <span>ส่งออกข้อมูล</span>
                    <small>Excel</small>
                </a>
            </div>

            <form action="<?= e(url('/teacher')) ?>" method="get" class="filter-form teacher-filter-form" data-teacher-filter-form>
                <label>
                    <span class="teacher-filter-label">ค้นหานักเรียน</span>
                    <input name="q" type="search" value="<?= e($search) ?>" placeholder="ค้นหาชื่อหรือเลขประจำตัว" data-student-search>
                </label>
                <label>
                    <span class="teacher-filter-label">เลือกชั้นเรียน</span>
                    <select name="class" data-class-filter>
                        <option value="">ทุกชั้นเรียน</option>
                        <?php foreach ($classNames as $className): ?>
                            <option value="<?= e($className) ?>" <?= $selectedClass === $className ? 'selected' : '' ?>><?= e($className) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
        </div>

        <div class="teacher-table-card">
            <div class="teacher-table-scroll">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th>เลขประจำตัว</th>
                            <th>ชื่อจริง - นามสกุล</th>
                            <th>ห้องเรียน</th>
                            <th>Rank</th>
                            <th>หยดสาร</th>
                            <th>ปรับหยด</th>
                            <th class="teacher-actions-heading">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$students): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="table-empty">ยังไม่มีนักเรียนในรายการ</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($students as $student): ?>
                            <?= \App\Support\View::partial('partials/student-row', ['student' => $student, 'ranks' => $ranks, 'csrf' => $csrf]) ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <?php if ($passwordResetNotice): ?>
        <dialog class="teacher-reset-modal" data-password-reset-modal data-password-reset-auto-open aria-labelledby="password-reset-title">
            <div class="teacher-reset-modal-panel">
                <button class="modal-close teacher-reset-modal-close" type="button" data-password-reset-close aria-label="ปิด">×</button>
                <span class="teacher-reset-aura teacher-reset-aura-mint" aria-hidden="true"></span>
                <span class="teacher-reset-aura teacher-reset-aura-gold" aria-hidden="true"></span>
                <div class="teacher-reset-icon" aria-hidden="true"></div>
                <div class="teacher-reset-copy">
                    <small>รีเซ็ตรหัสผ่านสำเร็จ</small>
                    <h2 id="password-reset-title">ตั้งรหัสผ่านใหม่เรียบร้อย</h2>
                    <p>
                        <?= e((string) ($passwordResetNotice['student_name'] ?? 'นักเรียน')) ?>
                        <?php if (!empty($passwordResetNotice['student_number'])): ?>
                            <span>เลขประจำตัว <?= e((string) $passwordResetNotice['student_number']) ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="teacher-reset-password-card" aria-label="รหัสผ่านใหม่">
                    <span>รหัสผ่านใหม่</span>
                    <strong><?= e((string) ($passwordResetNotice['password'] ?? '12345678')) ?></strong>
                </div>
                <button class="btn btn-primary teacher-reset-done" type="button" data-password-reset-close>รับทราบ</button>
            </div>
        </dialog>
    <?php endif; ?>

    <dialog class="teacher-import-modal" data-import-modal aria-labelledby="import-modal-title">
        <div class="teacher-import-modal-panel">
            <button class="modal-close teacher-import-modal-close" type="button" data-import-modal-close aria-label="ปิด">×</button>
            <span class="teacher-import-modal-aura teacher-import-modal-aura-mint" aria-hidden="true"></span>
            <span class="teacher-import-modal-aura teacher-import-modal-aura-rose" aria-hidden="true"></span>
            <div class="teacher-import-modal-head">
                <span class="teacher-import-modal-icon" aria-hidden="true"></span>
                <div class="teacher-import-modal-title">
                    <small>ตรวจไฟล์รายชื่อ</small>
                    <h2 id="import-modal-title">ตรวจรายชื่อก่อนนำเข้า</h2>
                    <p data-import-modal-summary>เลือกไฟล์รายชื่อเพื่อดูข้อมูลทั้งหมดก่อนบันทึกเข้าระบบ</p>
                </div>
                <div class="teacher-import-modal-count" data-import-modal-count>
                    <strong>0</strong>
                    <span>รายชื่อ</span>
                </div>
            </div>
            <div class="teacher-import-modal-body">
                <div class="teacher-import-loading" data-import-loading hidden>
                    <span aria-hidden="true"></span>
                    <div>
                        <strong>กำลังอ่านไฟล์รายชื่อ</strong>
                        <small>ระบบกำลังตรวจคอลัมน์และจัดข้อมูลให้อ่านง่าย</small>
                    </div>
                </div>
                <div class="teacher-import-error" data-import-error hidden></div>
                <div class="teacher-import-preview-table table-scroll" data-import-preview hidden>
                    <table>
                        <thead>
                            <tr>
                                <th>แถว</th>
                                <th>เลขประจำตัว</th>
                                <th>ชื่อจริง - นามสกุล</th>
                                <th>ห้องเรียน</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody data-import-preview-body></tbody>
                    </table>
                </div>
            </div>
            <div class="teacher-import-modal-actions">
                <button class="btn teacher-import-secondary-action" type="button" data-import-reset>เลือกไฟล์ใหม่</button>
                <button class="btn btn-secondary teacher-import-primary-action" type="button" data-import-submit disabled>นำเข้ารายชื่อ</button>
            </div>
        </div>
    </dialog>
</section>
