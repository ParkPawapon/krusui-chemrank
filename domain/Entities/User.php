<?php

declare(strict_types=1);

namespace Domain\Entities;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $role,
        public readonly string $username,
        public readonly string $passwordHash,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }
}

