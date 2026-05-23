<?php

declare(strict_types=1);

namespace Domain\ValueObjects;

final class Role
{
    public const STUDENT = 'student';
    public const TEACHER = 'teacher';

    public static function isValid(string $role): bool
    {
        return in_array($role, [self::STUDENT, self::TEACHER], true);
    }
}

