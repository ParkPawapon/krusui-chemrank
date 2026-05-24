<?php

use App\Services\CsrfTokenManager;
use App\Support\Flash;
use Domain\Rank\RankRegistry;

$siteName = (string) config('app.name', 'Chem Rank');
$rawTitle = 'Chem Rank เส้นทางสู่นักเคมี | kmewithsui';
$metaDescription = (string) ($description ?? config('seo.description', 'ระบบสะสมหยดสารสำหรับห้องเรียนเคมี'));
$metaKeywords = (string) config('seo.keywords', 'Chem Rank, เคมี, หยดสาร');
$metaRobots = (string) ($robots ?? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1');
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$canonicalUrl = (string) ($canonical ?? absolute_url((string) ($canonicalPath ?? $requestPath)));
$ogImageUrl = absolute_url((string) ($image ?? config('seo.image', '/assets/brand/favicon-192x192.png')));
$ogLocale = (string) config('seo.locale', 'th_TH');
$twitterCard = (string) config('seo.twitter_card', 'summary_large_image');
$flashMessages = Flash::all();
$currentRole = $_SESSION['role'] ?? null;
$layoutCsrf = new CsrfTokenManager();
$modalDefaultRank = (new RankRegistry())->all()[0];
$brandUrl = $currentRole === 'student'
    ? url('/student')
    : ($currentRole === 'teacher' ? url('/teacher') : url('/'));
$navItems = match ($currentRole) {
    'student' => [
        ['/student', 'ห้องทดลอง'],
        ['/leaderboard', 'อันดับหยดสาร'],
        ['/student/profile', 'โปรไฟล์'],
    ],
    'teacher' => [
        ['/teacher', 'ห้องครู'],
        ['/leaderboard', 'อันดับห้องเรียน'],
        ['/teacher/profile', 'โปรไฟล์'],
    ],
    default => [
        ['/leaderboard', 'อันดับหยดสาร'],
        ['/ranks', 'แผนที่ Rank'],
    ],
};
?>
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="csrf-token" content="<?= e($layoutCsrf->token()) ?>">
    <meta name="theme-color" content="#69d7c6">
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="keywords" content="<?= e($metaKeywords) ?>">
    <meta name="robots" content="<?= e($metaRobots) ?>">
    <meta name="application-name" content="<?= e($siteName) ?>">
    <meta name="apple-mobile-web-app-title" content="<?= e($siteName) ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="<?= e($ogLocale) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:title" content="<?= e($rawTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:image" content="<?= e($ogImageUrl) ?>">
    <meta name="twitter:card" content="<?= e($twitterCard) ?>">
    <meta name="twitter:title" content="<?= e($rawTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
    <meta name="twitter:image" content="<?= e($ogImageUrl) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <link rel="manifest" href="<?= e(url('/site.webmanifest')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(asset('brand/favicon-32x32.png')) ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= e(asset('brand/favicon-192x192.png')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(asset('brand/apple-touch-icon.png')) ?>">
    <link rel="preload" as="image" type="image/webp" href="<?= e(asset('brand/chem-rank-loader.webp')) ?>" fetchpriority="high">
    <title><?= e($rawTitle) ?></title>
    <script src="<?= e(asset('js/loader-boot.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="min-h-screen bg-mint-50 text-ink antialiased" itemscope itemtype="https://schema.org/WebApplication">
    <meta itemprop="name" content="<?= e($siteName) ?>">
    <meta itemprop="applicationCategory" content="EducationalApplication">
    <meta itemprop="operatingSystem" content="Web">
    <div class="page-loader" data-page-loader role="status" aria-live="polite" aria-label="กำลังโหลดหน้าเว็บ">
        <div class="page-loader-panel">
            <span class="page-loader-drop page-loader-drop-a" aria-hidden="true"></span>
            <span class="page-loader-drop page-loader-drop-b" aria-hidden="true"></span>
            <span class="page-loader-drop page-loader-drop-c" aria-hidden="true"></span>
            <img class="page-loader-logo" src="<?= e(asset('brand/chem-rank-loader.webp')) ?>" width="1245" height="300" alt="" loading="eager" decoding="sync" fetchpriority="high">
            <div class="page-loader-bar" aria-hidden="true"><span></span></div>
            <p>กำลังเตรียมห้องทดลอง</p>
        </div>
    </div>

    <div class="site-shell">
        <header class="site-header">
            <a href="<?= e($brandUrl) ?>" class="brand-mark" aria-label="<?= e($currentRole === 'student' ? 'ห้องทดลองของฉัน' : 'หน้าแรก') ?>">
                <span class="brand-logo-frame" aria-hidden="true">
                    <img class="brand-logo-image" src="<?= e(asset('brand/chem.svg')) ?>" alt="" loading="eager" decoding="async">
                </span>
            </a>

            <nav class="site-nav" aria-label="เมนูหลัก">
                <?php foreach ($navItems as [$path, $label]): ?>
                    <a href="<?= e(url($path)) ?>" class="<?= $requestPath === $path ? 'is-active' : '' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
                <?php if ($currentRole): ?>
                    <form action="<?= e(url('/logout')) ?>" method="post" class="inline">
                        <?= $layoutCsrf->field() ?>
                        <button class="nav-logout" type="submit">ออกจากระบบ</button>
                    </form>
                <?php endif; ?>
            </nav>
        </header>

        <?php if ($flashMessages): ?>
            <?php
            $flashTitles = [
                'success' => 'เรียบร้อยแล้ว',
                'error' => 'ลองตรวจดูอีกครั้ง',
                'warning' => 'ตรวจสอบอีกครั้ง',
                'info' => 'แจ้งให้ทราบ',
            ];
            ?>
            <section class="flash-stack" aria-live="polite" aria-label="ข้อความแจ้งเตือน">
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <?php $flashTitle = $flashTitles[$type] ?? $flashTitles['info']; ?>
                        <article class="flash-message flash-<?= e($type) ?>" data-flash-message role="<?= $type === 'error' ? 'alert' : 'status' ?>">
                            <span class="flash-icon" aria-hidden="true"></span>
                            <span class="flash-spark flash-spark-a" aria-hidden="true"></span>
                            <span class="flash-spark flash-spark-b" aria-hidden="true"></span>
                            <div class="flash-copy">
                                <strong><?= e($flashTitle) ?></strong>
                                <p><?= e($message) ?></p>
                            </div>
                            <button class="flash-close" type="button" data-flash-close aria-label="ปิดข้อความแจ้งเตือน">×</button>
                        </article>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <main>
            <?= $content ?>
        </main>
    </div>

    <?= \App\Support\View::partial('partials/level-up-modal', ['defaultRank' => $modalDefaultRank]) ?>
    <div data-toast-root class="toast-root" aria-live="polite"></div>
    <script type="module" src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
