<?php
/** @var array<int,array<string,mixed>> $rankedStudents */
/** @var array<int,string> $classNames */
/** @var string $selectedClass */
$topGroups = [1 => [], 2 => [], 3 => []];
foreach ($rankedStudents as $entry) {
    $place = (int) $entry['place'];
    if ($place >= 1 && $place <= 3) {
        $topGroups[$place][] = $entry;
    }
}

$soloTopEntries = [];
$tiedTopGroups = [];
foreach ([1, 2, 3] as $place) {
    $groupCount = count($topGroups[$place]);
    if ($groupCount === 1) {
        $soloTopEntries[$place] = $topGroups[$place][0];
    } elseif ($groupCount > 1) {
        $tiedTopGroups[$place] = $topGroups[$place];
    }
}

$medalClassForPlace = static fn (int $place): string => $place === 1 ? 'gold' : ($place === 2 ? 'silver' : 'bronze');
$placeTitle = [1 => 'อันดับ 1', 2 => 'อันดับ 2', 3 => 'อันดับ 3'];
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

                    <?php if ($soloTopEntries): ?>
                    <div class="podium podium-featured podium-count-<?= e((string) count($soloTopEntries)) ?>">
                        <?php foreach ([2, 1, 3] as $place): ?>
                            <?php if (!isset($soloTopEntries[$place])) {
                                continue;
                            } ?>
                            <?php
                            $entry = $soloTopEntries[$place];
                            $student = $entry['student'];
                            $rank = $entry['rank'];
                            $progress = $entry['progress'];
                            ?>
                            <article class="podium-card podium-slot-<?= e((string) $place) ?> place-<?= e((string) $place) ?>" style="--rank-color: <?= e($rank->themeColor) ?>">
                                <?php if ($place === 1): ?>
                                    <span class="podium-crown" aria-hidden="true"></span>
                                <?php endif; ?>
                                <div class="podium-card-top">
                                    <span class="leaderboard-place <?= e($medalClassForPlace($place)) ?>">
                                        <small>อันดับ</small>
                                        <?= e((string) $place) ?>
                                    </span>
                                    <span class="podium-label"><?= e($place === 1 ? 'หยดสารสูงสุด' : $placeTitle[$place]) ?></span>
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
                    <?php endif; ?>

                    <?php foreach ($tiedTopGroups as $place => $entries): ?>
                        <?php
                        $firstEntry = $entries[0];
                        $firstStudent = $firstEntry['student'];
                        $firstRank = $firstEntry['rank'];
                        ?>
                        <section class="top-tie-panel top-tie-place-<?= e((string) $place) ?>" style="--rank-color: <?= e($firstRank->themeColor) ?>">
                            <div class="top-tie-head">
                                <span class="leaderboard-place <?= e($medalClassForPlace($place)) ?>">
                                    <small>อันดับ</small>
                                    <?= e((string) $place) ?>
                                </span>
                                <div>
                                    <h3><?= e($placeTitle[$place]) ?> ร่วม</h3>
                                    <p><?= e((string) count($entries)) ?> คนมี <?= e((string) $firstStudent->drops) ?> หยดสารเท่ากัน</p>
                                </div>
                            </div>

                            <div class="top-tie-table-wrap" role="region" aria-label="<?= e($placeTitle[$place]) ?> ร่วม">
                                <table class="top-tie-table">
                                    <thead>
                                        <tr>
                                            <th>นักเรียน</th>
                                            <th>ห้องเรียน</th>
                                            <th>Rank</th>
                                            <th>หยดสาร</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($entries as $entry): ?>
                                            <?php
                                            $student = $entry['student'];
                                            $rank = $entry['rank'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="top-tie-student">
                                                        <span class="top-tie-rank-icon">
                                                            <?= \App\Support\View::partial('partials/rank-badge', ['rank' => $rank, 'size' => 'sm', 'showName' => false]) ?>
                                                        </span>
                                                        <strong><?= e($student->fullName) ?></strong>
                                                    </span>
                                                </td>
                                                <td><?= e($student->className) ?></td>
                                                <td><?= e($rank->thaiName) ?></td>
                                                <td><strong><?= e((string) $student->drops) ?></strong> หยดสาร</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </div>
</section>
