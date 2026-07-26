<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Wpdb;

use TpmPsych\Domain\Instrument\Instrument;
use TpmPsych\Repositories\Contracts\InstrumentRepositoryInterface;

final class WpdbInstrumentRepository implements InstrumentRepositoryInterface
{
    private readonly \wpdb $db;

    private readonly string $table;

    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
        $this->table = $this->db->prefix . 'tpm_instruments';
    }

    public function find(int $id): ?Instrument
    {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );

        return $row !== null ? Instrument::fromRow($row) : null;
    }

    public function findByCode(string $code): ?Instrument
    {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE code = %s", $code),
            ARRAY_A
        );

        return $row !== null ? Instrument::fromRow($row) : null;
    }

    public function list(?string $status = null): array
    {
        if ($status !== null) {
            $rows = $this->db->get_results(
                $this->db->prepare("SELECT * FROM {$this->table} WHERE status = %s ORDER BY name ASC", $status),
                ARRAY_A
            );
        } else {
            $rows = $this->db->get_results("SELECT * FROM {$this->table} ORDER BY name ASC", ARRAY_A);
        }

        return array_map(static fn (array $row) => Instrument::fromRow($row), $rows ?? []);
    }

    public function insert(Instrument $instrument, int $actorUserId): Instrument
    {
        $now = current_time('mysql', true);

        $this->db->insert($this->table, [
            'organization_id' => $instrument->organizationId,
            'code' => $instrument->code,
            'name' => $instrument->name,
            'rights_holder' => $instrument->rightsHolder,
            'license_reference' => $instrument->licenseReference,
            'target_population' => $instrument->targetPopulation,
            'status' => $instrument->status,
            'created_at' => $now,
            'created_by' => $actorUserId,
        ]);

        return $this->find((int) $this->db->insert_id);
    }

    public function update(Instrument $instrument, int $actorUserId): void
    {
        $this->db->update(
            $this->table,
            [
                'name' => $instrument->name,
                'rights_holder' => $instrument->rightsHolder,
                'license_reference' => $instrument->licenseReference,
                'target_population' => $instrument->targetPopulation,
                'status' => $instrument->status,
                'updated_at' => current_time('mysql', true),
                'updated_by' => $actorUserId,
            ],
            ['id' => $instrument->id]
        );
    }
}
