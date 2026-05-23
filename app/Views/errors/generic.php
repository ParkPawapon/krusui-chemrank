<section class="page-band min-h-[70vh] grid place-items-center">
    <div class="empty-state">
        <span class="pixel-lab-icon" aria-hidden="true"></span>
        <h1><?= e($title ?? 'เกิดข้อผิดพลาด') ?></h1>
        <p><?= e($message ?? 'กรุณาลองใหม่อีกครั้ง') ?></p>
        <a class="btn btn-primary" href="<?= e(url('/')) ?>">กลับหน้าแรก</a>
    </div>
</section>

