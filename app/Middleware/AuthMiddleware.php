<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\AuthService;
use App\Support\Response;

final class AuthMiddleware
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function requireRole(string $role): void
    {
        $user = $this->auth->currentUser();

        if (!$user) {
            Response::redirect('/login?role=' . $role);
        }

        if ($user->role !== $role) {
            Response::abort(403, 'บัญชีนี้ไม่มีสิทธิ์เข้าถึงหน้าดังกล่าว');
        }
    }
}

