<?php

declare(strict_types=1);

namespace Infrastructure\Persistence;

use Domain\Repositories\DropTransactionRepository;
use PDO;

final class PdoDropTransactionRepository implements DropTransactionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $studentId, int $teacherId, int $amount, string $type, ?string $reason): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO drop_transactions (student_id, teacher_id, amount, type, reason, created_at)
             VALUES (:student_id, :teacher_id, :amount, :type, :reason, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
            'amount' => $amount,
            'type' => $type,
            'reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
        ]);
    }
}

