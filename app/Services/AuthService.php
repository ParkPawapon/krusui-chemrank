<?php

declare(strict_types=1);

namespace App\Services;

use Domain\Entities\User;
use Domain\Repositories\UserRepository;
use Domain\ValueObjects\Role;
use Infrastructure\Security\LoginRateLimiter;
use Infrastructure\Security\PasswordHasher;
use Infrastructure\Security\SessionManager;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $passwords,
        private readonly LoginRateLimiter $rateLimiter,
        private readonly SessionManager $sessions,
    ) {
    }

    public function attempt(string $loginIdentifier, string $password, string $ip, ?string $expectedRole = null): ?User
    {
        $loginIdentifier = mb_strtolower(trim($loginIdentifier));

        if (
            $loginIdentifier === ''
            || $password === ''
            || ($expectedRole === Role::STUDENT && !preg_match('/^\d{5}$/', $loginIdentifier))
            || $this->rateLimiter->tooManyAttempts($ip, $loginIdentifier)
        ) {
            return null;
        }

        $user = $this->users->findByUsername($loginIdentifier);

        if (!$user || !$this->passwords->verify($password, $user->passwordHash)) {
            $this->rateLimiter->hit($ip, $loginIdentifier);
            return null;
        }

        if ($expectedRole !== null && Role::isValid($expectedRole) && $user->role !== $expectedRole) {
            $this->rateLimiter->hit($ip, $loginIdentifier);
            return null;
        }

        $this->rateLimiter->clear($ip, $loginIdentifier);
        $this->sessions->regenerate();
        $_SESSION['user_id'] = $user->id;
        $_SESSION['role'] = $user->role;

        return $user;
    }

    public function currentUser(): ?User
    {
        $id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

        return $id > 0 ? $this->users->findById($id) : null;
    }

    public function check(): bool
    {
        return $this->currentUser() !== null;
    }

    public function logout(): void
    {
        $this->sessions->destroy();
    }
}
