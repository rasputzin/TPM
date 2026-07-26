<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Wpdb;

use TpmPsych\Domain\Instrument\InstrumentVersion;
use TpmPsych\Repositories\Contracts\InstrumentVersionRepositoryInterface;

final class WpdbInstrumentVersionRepository implements InstrumentVersionRepositoryInterface
{
    private readonly \wpdb $db;

    private readonly string $table;

    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
        $this->table = $this->db->prefix . 'tpm_instrument_versions';
    }

    public function find(int $id): ?InstrumentVersion
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);

        return $row !== null ? InstrumentVersion::fromRow($row) : null;
    }

    public function findByInstrumentAndLabel(int $instrumentId, string $versionLabel): ?InstrumentVersion
    {
        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE instrument_id = %d AND version_label = %s",
                $instrumentId,
                $versionLabel
            ),
            ARRAY_A
        );

        return $row !== null ? InstrumentVersion::fromRow($row) : null;
    }

    public function findActiveForInstrument(int $instrumentId): ?InstrumentVersion
    {
        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE instrument_id = %d AND status = 'active' LIMIT 1",
                $instrumentId
            ),
            ARRAY_A
        );

        return $row !== null ? InstrumentVersion::fromRow($row) : null;
    }

    public function listByInstrument(int $instrumentId): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE instrument_id = %d ORDER BY id DESC",
                $instrumentId
            ),
            ARRAY_A
        );

        return array_map(static fn (array $row) => InstrumentVersion::fromRow($row), $rows ?? []);
    }

    public function insert(InstrumentVersion $version, int $actorUserId): InstrumentVersion
    {
        $now = current_time('mysql', true);

        $this->db->insert($this->table, [
            'instrument_id' => $version->instrumentId,
            'version_label' => $version->versionLabel,
            'language' => $version->language,
            'estimated_duration_minutes' => $version->estimatedDurationMinutes,
            'instructions_text' => $version->instructionsText,
            'missing_response_policy_json' => $version->missingResponsePolicy !== null
                ? wp_json_encode($version->missingResponsePolicy)
                : null,
            'status' => $version->status,
            'created_at' => $now,
            'created_by' => $actorUserId,
        ]);

        return $this->find((int) $this->db->insert_id);
    }

    public function update(InstrumentVersion $version, int $actorUserId): void
    {
        $this->db->update(
            $this->table,
            [
                'language' => $version->language,
                'estimated_duration_minutes' => $version->estimatedDurationMinutes,
                'instructions_text' => $version->instructionsText,
                'missing_response_policy_json' => $version->missingResponsePolicy !== null
                    ? wp_json_encode($version->missingResponsePolicy)
                    : null,
                'status' => $version->status,
                'published_at' => $version->status === InstrumentVersion::STATUS_ACTIVE
                    ? current_time('mysql', true)
                    : null,
                'updated_at' => current_time('mysql', true),
                'updated_by' => $actorUserId,
            ],
            ['id' => $version->id]
        );
    }
}
