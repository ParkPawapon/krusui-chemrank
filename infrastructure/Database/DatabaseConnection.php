<?php

declare(strict_types=1);

namespace Infrastructure\Database;

use PDO;

final class DatabaseConnection
{
    private ?PDO $pdo = null;

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $connection = (string) config('database.connection', 'sqlite');

        if ($connection === 'mysql') {
            $mysql = config('database.mysql');
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $mysql['host'],
                $mysql['port'],
                $mysql['database'],
                $mysql['charset']
            );
            $this->pdo = new PDO($dsn, (string) $mysql['username'], (string) $mysql['password'], $this->options());
        } else {
            $path = (string) config('database.sqlite_path');
            $dir = dirname($path);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $this->pdo = new PDO('sqlite:' . $path, null, null, $this->options());
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        }

        return $this->pdo;
    }

    /**
     * @return array<int,mixed>
     */
    private function options(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}

