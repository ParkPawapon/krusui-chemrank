<?php
/** @var \Domain\Rank\RankDefinition $defaultRank */
?>
<dialog class="level-modal" data-level-modal>
    <div class="level-modal-panel">
        <button class="modal-close" type="button" data-modal-close aria-label="ปิด">×</button>
        <div class="pixel-sparkle-field" aria-hidden="true"></div>
        <img class="level-modal-icon pixel-art" data-level-modal-icon src="<?= e($defaultRank->assetPath) ?>" width="112" height="112" alt="" decoding="async">
        <h2 data-level-modal-title>Rank เปลี่ยนแล้ว</h2>
        <p data-level-modal-message>เก็บหยดสารต่อไป</p>
        <button class="btn btn-primary" type="button" data-modal-close>ตกลง</button>
    </div>
</dialog>
