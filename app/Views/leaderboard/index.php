<?php
/** @var array<int,array<string,mixed>> $rankedStudents */
/** @var array<int,string> $classNames */
/** @var string $selectedClass */
$topThree = array_values(array_filter(
    $rankedStudents,
    static fn (array $entry): bool => (int) $entry['place'] <= 3
));
$studentCount = count($rankedStudents);
$topEntry = $rankedStudents[0] ?? null;
$topDrops = $topEntry ? (int) $topEntry['student']->drops : 0;
$selectedClassLabel = $selectedClass !== '' ? $selectedClass : 'ทุกชั้นเรียน';
$hasTie = false;

foreach ($rankedStudents as $entry) {
    if (!empty($entry['is_tied'])) {
        $hasTie = true;
        break;
    }
}
?>
<section class="leaderboard-page page-band">
    <div data-leaderboard-frame>
        <div class="leaderboard-hero">
            <div class="leaderboard-copy">
                <span class="eyebrow-pill">อันดับหยดสาร</span>
                <h1>บอร์ดความก้าวหน้า</h1>

                <div class="leaderboard-facts" aria-label="ภาพรวมอันดับหยดสาร">
                    <span><strong><?= e((string) $studentCount) ?></strong> นักเรียน</span>
                    <span><strong><?= e((string) $topDrops) ?></strong> หยดสูงสุด</span>
                    <span><strong><?= e($selectedClassLabel) ?></strong></span>
                    <?php if ($hasTie): ?>
                        <span><strong>มีอันดับร่วม</strong></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="leaderboard-toolbar">
            <div class="leaderboard-toolbar-title">
                <span>จัดอันดับ</span>
                <strong><?= e($selectedClassLabel) ?></strong>
            </div>
            <form action="<?= e(url('/leaderboard')) ?>" method="get" class="leaderboard-filter" aria-label="เลือกชั้นเรียน" data-leaderboard-filter-form>
                <label for="class-filter">ชั้นเรียน</label>
                <select id="class-filter" name="class" data-leaderboard-class-filter>
                    <option value="">ทุกชั้นเรียน</option>
                    <?php foreach ($classNames as $className): ?>
                        <option value="<?= e($className) ?>" <?= $selectedClass === $className ? 'selected' : '' ?>><?= e($className) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div data-leaderboard-results aria-live="polite">
            <?php if (!$rankedStudents): ?>
                <div class="empty-state leaderboard-empty">
                    <span class="pixel-lab-icon" aria-hidden="true"></span>
                    <h2>ยังไม่มีอันดับหยดสาร</h2>
                    <p>เมื่อครูเพิ่มนักเรียน รายชื่อจะปรากฏที่นี่</p>
                </div>
            <?php else: ?>
                <section class="podium-section" aria-labelledby="podium-title">
                    <div class="leaderboard-section-heading">
                        <div>
                            <h2 id="podium-title">3 อันดับแรก</h2>
                        </div>
                    </div>

                    <div class="podium">
                        <?php foreach ($topThree as $podiumIndex => $entry): ?>
                            <?php
                            $place = (int) $entry['place'];
                            $student = $entry['student'];
                            $rank = $entry['rank'];
                            $progress = $entry['progress'];
                            $isTied = !empty($entry['is_tied']);
                            ?>
                            <article class="podium-card podium-slot-<?= e((string) ($podiumIndex + 1)) ?> place-<?= e((string) min(3, $place)) ?> <?= $isTied ? 'is-tied' : '' ?>" style="--rank-color: <?= e($rank->themeColor) ?>">
                                <div class="podium-card-top">
                                    <span class="leaderboard-place <?= e($place === 1 ? 'gold' : ($place === 2 ? 'silver' : 'bronze')) ?>">
                                        <small>อันดับ</small>
                                        <?= e((string) $place) ?>
                                    </span>
                                    <?php if ($isTied): ?>
                                        <span class="tie-chip">อันดับร่วม</span>
                                    <?php endif; ?>
                                </div>
                                <div class="podium-rank-icon">
                                    <?= \App\Support\View::partial('partials/rank-badge', ['rank' => $rank, 'size' => 'md', 'showName' => false]) ?>
                                </div>
                                <h3><?= e($student->fullName) ?></h3>
                                <p><?= e($student->className) ?> · <?= e($rank->thaiName) ?></p>
                                <strong><?= e((string) $student->drops) ?> หยดสาร</strong>
                                <div class="podium-progress" aria-label="<?= e($progress->message) ?>">
                                    <span style="width: <?= e((string) $progress->percentToNext) ?>%"></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</section>
