<?php

declare(strict_types=1);

namespace Infrastructure\Persistence;

use Domain\Entities\User;
use Domain\Repositories\UserRepository;
use PDO;

final class PdoUserRepository implements UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?User
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => mb_strtolower(trim($username))]);
        $row = $statement->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function usernameExists(string $username): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $statement->execute(['username' => mb_strtolower(trim($username))]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(string $name, string $role, string $username, string $passwordHash): User
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, role, username, password_hash, created_at, updated_at)
             VALUES (:name, :role, :username, :password_hash, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            'name' => trim($name),
            'role' => $role,
            'username' => mb_strtolower(trim($username)),
            'password_hash' => $passwordHash,
        ]);

        return $this->findById((int) $this->pdo->lastInsertId())
            ?? throw new \RuntimeException('Unable to create user.');
    }

    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute([
            'id' => $userId,
            'password_hash' => $passwordHash,
        ]);

        if ($statement->rowCount() < 1) {
            throw new \RuntimeException('Unable to update password.');
        }
    }

    public function deleteOrphanedStudentAccounts(): int
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $statement = $this->pdo->prepare(
                'DELETE u
                 FROM users u
                 LEFT JOIN students s ON s.user_id = u.id
                 WHERE u.role = :role AND s.id IS NULL'
            );
        } else {
            $statement = $this->pdo->prepare(
                'DELETE FROM users
                 WHERE role = :role
                 AND id NOT IN (
                     SELECT user_id FROM students WHERE user_id IS NOT NULL
                 )'
            );
        }

        $statement->execute(['role' => 'student']);

        return $statement->rowCount();
    }

    /**
     * @param array<string,mixed> $row
     */
    private function hydrate(array $row): User
    {
        return new User(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['role'],
            (string) $row['username'],
            (string) $row['password_hash'],
            (string) $row['created_at'],
            (string) $row['updated_at'],
        );
    }
}
