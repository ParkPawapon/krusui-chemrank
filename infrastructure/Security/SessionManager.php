<?php

declare(strict_types=1);

namespace Infrastructure\Security;

final class SessionManager
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name((string) config('security.session_name'));
        session_set_cookie_params([
            'lifetime' => (int) config('security.session_lifetime'),
            'path' => '/',
            'domain' => '',
            'secure' => (bool) config('security.secure_cookies'),
            'httponly' => true,
            'samesite' => (string) config('security.same_site', 'Lax'),
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}

