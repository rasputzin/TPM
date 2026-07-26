<?php

declare(strict_types=1);

namespace TpmPsych\Services;

interface AuditServiceInterface
{
    /** @param array<string, scalar|null> $metadata */
    public function log(
        string $eventType,
        string $entityType,
        ?int $entityId,
        ?int $actorUserId,
        array $metadata,
        ?int $organizationId = null
    ): void;
}
