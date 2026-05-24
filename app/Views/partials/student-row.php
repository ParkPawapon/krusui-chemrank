<?php
/** @var \Domain\Entities\Student $student */
/** @var \Domain\Rank\RankRegistry $ranks */
/** @var \App\Services\CsrfTokenManager $csrf */
$rank = $ranks->forDrops($student->drops);
$classroom = $student->className;
?>
<tr data-student-row data-student-id="<?= e((string) $student->id) ?>" data-rank-level="<?= e((string) $rank->level) ?>">
    <td><span class="soft-pill student-number-pill"><?= e($student->studentNumber) ?></span></td>
    <td class="student-name-cell">
        <span class="student-identity">
            <span class="student-avatar" aria-hidden="true"></span>
            <span>
                <strong><?= e($student->fullName) ?></strong>
                <small>นักเรียน</small>
            </span>
        </span>
    </td>
    <td><span class="classroom-pill"><?= e($classroom) ?></span></td>
    <td class="teacher-rank-cell" data-rank-cell>
        <?= \App\Support\View::partial('partials/rank-badge', ['rank' => $rank, 'size' => 'sm']) ?>
    </td>
    <td>
        <div class="drop-count">
            <span data-drops-value><?= e((string) $student->drops) ?></span>
            <small>หยดสาร</small>
        </div>
    </td>
    <td>
        <form class="drop-action-form" data-drop-form action="<?= e(url('/teacher/drops')) ?>" method="post">
            <?= $csrf->field() ?>
            <input type="hidden" name="student_id" value="<?= e((string) $student->id) ?>">
            <label class="sr-only" for="amount-<?= e((string) $student->id) ?>">จำนวนหยด</label>
            <input id="amount-<?= e((string) $student->id) ?>" class="table-input" name="amount" type="number" min="1" max="999" value="1" inputmode="numeric">
            <button class="icon-action add" name="type" value="add" type="submit" title="เพิ่มหยดสาร" aria-label="เพิ่มหยดสาร">
                <span aria-hidden="true">+</span>
            </button>
            <button class="icon-action subtract" name="type" value="subtract" type="submit" title="ลดหยดสาร" aria-label="ลดหยดสาร">
                <span aria-hidden="true">−</span>
            </button>
        </form>
    </td>
    <td class="teacher-table-actions">
        <div class="teacher-action-group" aria-label="จัดการนักเรียน <?= e($student->fullName) ?>">
            <form action="<?= e(url('/teacher/students/reset-password')) ?>" method="post" data-confirm="รีเซ็ตรหัสผ่านนักเรียนคนนี้หรือไม่?">
                <?= $csrf->field() ?>
                <input type="hidden" name="student_id" value="<?= e((string) $student->id) ?>">
                <button
                    class="teacher-row-action teacher-row-action-reset"
                    type="submit"
                    aria-label="รีเซ็ตรหัสผ่าน <?= e($student->fullName) ?>"
                    data-tooltip="รีเซ็ตรหัสผ่าน"
                >
                    <span aria-hidden="true"></span>
                </button>
            </form>
            <form action="<?= e(url('/teacher/students/delete')) ?>" method="post" data-confirm="ลบนักเรียนคนนี้หรือไม่?">
                <?= $csrf->field() ?>
                <input type="hidden" name="student_id" value="<?= e((string) $student->id) ?>">
                <button
                    class="teacher-row-action teacher-row-action-delete"
                    type="submit"
                    aria-label="ลบนักเรียน <?= e($student->fullName) ?>"
                    data-tooltip="ลบนักเรียน"
                >
                    <span aria-hidden="true"></span>
                </button>
            </form>
        </div>
    </td>
</tr>
