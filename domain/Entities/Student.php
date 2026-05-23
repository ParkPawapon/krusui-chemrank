<?php

declare(strict_types=1);

namespace Domain\Entities;

final class Student
{
    public readonly string $className;

    public function __construct(
        public readonly int $id,
        public readonly ?int $userId,
        public readonly string $studentNumber,
        public readonly string $fullName,
        public readonly string $classLevel,
        public readonly string $room,
        public readonly int $academicYearId,
        public readonly string $academicYearName,
        public readonly int $drops,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
        $this->className = self::formatClassName($this->classLevel, $this->room);
    }

    public static function formatClassName(string $classLevel, string $room): string
    {
        $classLevel = trim($classLevel);
        $room = trim($room);

        return $room !== '' ? $classLevel . '/' . $room : $classLevel;
    }
}
