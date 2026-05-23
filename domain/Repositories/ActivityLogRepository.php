<?php

declare(strict_types=1);

namespace Domain\Repositories;

interface ActivityLogRepository
{
    /**
     * @param array<string,mixed> $metadata
     */
    public function record(?int $actorUserId, string $action, string $entityType, ?int $entityId, array $metadata = []): void;
}
