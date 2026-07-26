<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Wpdb;

use TpmPsych\Domain\Instrument\Scale;
use TpmPsych\Repositories\Contracts\ScaleRepositoryInterface;

final class WpdbScaleRepository implements ScaleRepositoryInterface
{
    private readonly \wpdb $db;

    private readonly string $table;

    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
        $this->table = $this->db->prefix . 'tpm_scales';
    }

    public function find(int $id): ?Scale
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);

        return $row !== null ? Scale::fromRow($row) : null;
    }

    public function listByInstrumentVersion(int $instrumentVersionId): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE instrument_version_id = %d ORDER BY id ASC", $instrumentVersionId),
            ARRAY_A
        );

        return array_map(static fn (array $row) => Scale::fromRow($row), $rows ?? []);
    }

    public function insert(Scale $scale): Scale
    {
        $this->db->insert($this->table, [
            'instrument_version_id' => $scale->instrumentVersionId,
            'parent_scale_id' => $scale->parentScaleId,
            'code' => $scale->code,
            'name' => $scale->name,
        ]);

        return $this->find((int) $this->db->insert_id);
    }

    public function update(Scale $scale): void
    {
        $this->db->update(
            $this->table,
            [
                'parent_scale_id' => $scale->parentScaleId,
                'code' => $scale->code,
                'name' => $scale->name,
            ],
            ['id' => $scale->id]
        );
    }

    public function delete(int $id): void
    {
        $this->db->delete($this->table, ['id' => $id]);
    }
}
