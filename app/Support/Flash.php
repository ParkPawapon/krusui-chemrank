<?php

declare(strict_types=1);

namespace App\Support;

final class Flash
{
    public static function put(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    /**
     * @return array<string,array<int,string>>
     */
    public static function all(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return is_array($messages) ? $messages : [];
    }
}

