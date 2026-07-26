<?php

declare(strict_types=1);

namespace TpmPsych\Tests\Fixtures;

use TpmPsych\Services\AuditServiceInterface;

final class InMemoryAuditService implements AuditServiceInterface
{
    /** @var list<array{event_type: string, entity_type: string, entity_id: ?int}> */
    public array $events = [];

    public function log(
        string $eventType,
        string $entityType,
        ?int $entityId,
        ?int $actorUserId,
        array $metadata,
        ?int $organizationId = null
    ): void {
        $this->events[] = [
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ];
    }
}
