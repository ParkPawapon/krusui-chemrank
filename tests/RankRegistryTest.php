<?php

declare(strict_types=1);

use Domain\Rank\RankRegistry;

$registry = new RankRegistry();

$cases = [
    0 => 'ละอองแรก',
    10 => 'ละอองแรก',
    11 => 'หยดต้นกำเนิด',
    25 => 'หยดต้นกำเนิด',
    26 => 'สารผสมเริ่มต้น',
    45 => 'สารผสมเริ่มต้น',
    46 => 'สารละลายก่อตัว',
    70 => 'สารละลายก่อตัว',
    71 => 'สารเข้มข้น',
    100 => 'สารเข้มข้น',
    101 => 'ภาวะอิ่มตัว',
    135 => 'ภาวะอิ่มตัว',
    136 => 'เหนือจุดอิ่มตัว',
    180 => 'เหนือจุดอิ่มตัว',
    181 => 'เหนือจุดอิ่มตัว',
];

foreach ($cases as $drops => $expectedRank) {
    assert_same($expectedRank, $registry->forDrops($drops)->thaiName, "Wrong rank for {$drops} drops");
}

$progress = $registry->progress(8);
assert_same(3, $progress->dropsNeededForNext, 'Progress should count drops to next rank');
assert_same('หยดต้นกำเนิด', $progress->next?->thaiName, 'Next rank should be Origin Drop');

$maxProgress = $registry->progress(240);
assert_same(100, $maxProgress->percentToNext, 'Max rank progress should stay full');
assert_same(null, $maxProgress->next, 'Max rank should not have next rank');

