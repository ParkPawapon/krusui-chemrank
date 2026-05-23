<?php

declare(strict_types=1);

namespace Infrastructure\Persistence;

use Domain\Repositories\ActivityLogRepository;
use PDO;

final class PdoActivityLogRepository implements ActivityLogRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function record(?int $actorUserId, string $action, string $entityType, ?int $entityId, array $metadata = []): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO activity_logs (actor_user_id, action, entity_type, entity_id, metadata, created_at)
             VALUES (:actor_user_id, :action, :entity_type, :entity_id, :metadata, CURRENT_TIMESTAMP)'
        );
        $statement->execute([
            'actor_user_id' => $actorUserId,
            'action' => trim($action),
            'entity_type' => trim($entityType),
            'entity_id' => $entityId,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
