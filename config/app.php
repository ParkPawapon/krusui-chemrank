<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => env('APP_NAME', 'Chem Rank'),
        'env' => env('APP_ENV', 'production'),
        'debug' => (bool) env('APP_DEBUG', false),
        'url' => env('APP_URL', 'http://localhost:8080'),
        'timezone' => env('APP_TIMEZONE', 'Asia/Bangkok'),
    ],
    'seo' => [
        'title' => env('SEO_TITLE', 'Chem Rank ระบบสะสมหยดสารสำหรับห้องเรียนเคมี'),
        'description' => env('SEO_DESCRIPTION', 'ระบบสะสมหยดสารและ Rank สำหรับนักเรียนมัธยม ช่วยให้ครูดูแลคะแนนได้ง่าย และให้นักเรียนเห็นความก้าวหน้าของตัวเองอย่างชัดเจน'),
        'keywords' => env('SEO_KEYWORDS', 'Chem Rank, เคมี, นักเรียนมัธยม, หยดสาร, ระบบสะสมคะแนน, ห้องเรียนเคมี'),
        'image' => env('SEO_IMAGE', '/assets/brand/favicon-192x192.png'),
        'locale' => env('SEO_LOCALE', 'th_TH'),
        'twitter_card' => env('SEO_TWITTER_CARD', 'summary_large_image'),
    ],
    'database' => [
        'connection' => env('DB_CONNECTION', 'sqlite'),
        'sqlite_path' => env('DB_DATABASE', storage_path('database.sqlite')),
        'mysql' => [
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'chem_rank'),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
        ],
    ],
    'security' => [
        'session_name' => env('SESSION_NAME', 'chem_rank_session'),
        'session_lifetime' => (int) env('SESSION_LIFETIME', 7200),
        'secure_cookies' => (bool) env('SESSION_SECURE_COOKIE', false),
        'same_site' => env('SESSION_SAME_SITE', 'Lax'),
        'login_attempts' => (int) env('LOGIN_ATTEMPTS', 5),
        'login_decay_seconds' => (int) env('LOGIN_DECAY_SECONDS', 900),
    ],
];
