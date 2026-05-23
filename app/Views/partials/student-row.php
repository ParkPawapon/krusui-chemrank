<?php
/** @var \Domain\Entities\Student $student */
/** @var \Domain\Rank\RankRegistry $ranks */
/** @var \App\Services\CsrfTokenManager $csrf */
$rank = $ranks->forDrops($student->drops);
$classroom = trim($student->classLevel . '/' . $student->room, '/');
?>
<tr data-student-row data-student-id="<?= e((string) $student->id) ?>" data-rank-level="<?= e((string) $rank->level) ?>">
    <td><span class="soft-pill"><?= e($student->studentNumber) ?></span></td>
    <td class="student-name-cell"><?= e($student->fullName) ?></td>
    <td><span class="classroom-pill"><?= e($classroom) ?></span></td>
    <td><?= e($student->academicYearName) ?></td>
    <td data-rank-cell>
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
    <td class="text-right">
        <form action="<?= e(url('/teacher/students/delete')) ?>" method="post" data-confirm="ลบนักเรียนคนนี้หรือไม่?">
            <?= $csrf->field() ?>
            <input type="hidden" name="student_id" value="<?= e((string) $student->id) ?>">
            <button class="text-action danger" type="submit">ลบ</button>
        </form>
    </td>
</tr>
