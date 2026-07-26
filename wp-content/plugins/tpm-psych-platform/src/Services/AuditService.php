<?php

declare(strict_types=1);

namespace TpmPsych\Services;

/**
 * Jurnalizare append-only, apelată din Service Layer (nu din Controllere,
 * ca să nu poată fi omisă accidental la adăugarea unui endpoint nou).
 *
 * NU acceptă niciodată conținut clinic (text de item, răspuns, formulă) în
 * $metadata — doar identificatori și fapte tehnice (ex. "engine_version").
 */
final class AuditService implements AuditServiceInterface
{
    private readonly \wpdb $db;

    private readonly string $table;

    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
        $this->table = $this->db->prefix . 'tpm_audit_logs';
    }

    /** @param array<string, scalar|null> $metadata */
    public function log(
        string $eventType,
        string $entityType,
        ?int $entityId,
        ?int $actorUserId,
        array $metadata,
        ?int $organizationId = null
    ): void {
        $this->db->insert($this->table, [
            'organization_id' => $organizationId,
            'actor_type' => $actorUserId !== null ? 'professional' : 'system',
            'actor_id' => $actorUserId,
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata_json' => $metadata !== [] ? wp_json_encode($metadata) : null,
            'created_at' => current_time('mysql', true),
        ]);
    }
}
