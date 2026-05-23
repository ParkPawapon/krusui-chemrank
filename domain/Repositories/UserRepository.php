<?php

declare(strict_types=1);

namespace Domain\Repositories;

use Domain\Entities\User;

interface UserRepository
{
    public function findById(int $id): ?User;

    public function findByUsername(string $username): ?User;

    public function usernameExists(string $username): bool;

    public function create(string $name, string $role, string $username, string $passwordHash): User;
}

