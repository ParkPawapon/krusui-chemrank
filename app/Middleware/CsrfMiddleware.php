<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\CsrfTokenManager;
use App\Support\Request;
use App\Support\Response;

final class CsrfMiddleware
{
    public function __construct(private readonly CsrfTokenManager $csrf)
    {
    }

    public function handle(Request $request): void
    {
        if ($request->method() !== 'POST') {
            return;
        }

        if (!$this->csrf->validate((string) $request->post('_csrf', ''))) {
            if ($request->expectsJson()) {
                Response::json(['ok' => false, 'message' => 'CSRF token ไม่ถูกต้อง'], 419);
            }

            Response::abort(419, 'แบบฟอร์มหมดอายุ กรุณาลองใหม่อีกครั้ง');
        }
    }
}

