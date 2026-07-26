<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Wpdb;

use TpmPsych\Domain\Scoring\NormEntry;
use TpmPsych\Domain\Scoring\NormSet;
use TpmPsych\Repositories\Contracts\NormRepositoryInterface;

final class WpdbNormRepository implements NormRepositoryInterface
{
    private readonly \wpdb $db;

    private readonly string $setsTable;

    private readonly string $entriesTable;

    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
        $this->setsTable = $this->db->prefix . 'tpm_norm_sets';
        $this->entriesTable = $this->db->prefix . 'tpm_norm_entries';
    }

    public function findSet(int $id): ?NormSet
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->setsTable} WHERE id = %d", $id), ARRAY_A);

        return $row !== null ? NormSet::fromRow($row) : null;
    }

    public function findActiveSetForInstrumentVersion(int $instrumentVersionId): ?NormSet
    {
        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->setsTable} WHERE instrument_version_id = %d AND status = 'active' LIMIT 1",
                $instrumentVersionId
            ),
            ARRAY_A
        );

        return $row !== null ? NormSet::fromRow($row) : null;
    }

    public function listSetsByInstrumentVersion(int $instrumentVersionId): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$this->setsTable} WHERE instrument_version_id = %d ORDER BY id DESC", $instrumentVersionId),
            ARRAY_A
        );

        return array_map(static fn (array $row) => NormSet::fromRow($row), $rows ?? []);
    }

    public function insertSet(NormSet $set): NormSet
    {
        $now = current_time('mysql', true);

        $this->db->insert($this->setsTable, [
            'instrument_version_id' => $set->instrumentVersionId,
            'label' => $set->label,
            'applicable_criteria_json' => $set->applicableCriteria !== null ? wp_json_encode($set->applicableCriteria) : null,
            'version_label' => $set->versionLabel,
            'status' => $set->status,
            'created_at' => $now,
        ]);

        return $this->findSet((int) $this->db->insert_id);
    }

    public function updateSet(NormSet $set): void
    {
        $this->db->update(
            $this->setsTable,
            [
                'label' => $set->label,
                'applicable_criteria_json' => $set->applicableCriteria !== null ? wp_json_encode($set->applicableCriteria) : null,
                'status' => $set->status,
                'updated_at' => current_time('mysql', true),
            ],
            ['id' => $set->id]
        );
    }

    public function findEntry(int $id): ?NormEntry
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->entriesTable} WHERE id = %d", $id), ARRAY_A);

        return $row !== null ? NormEntry::fromRow($row) : null;
    }

    public function listEntries(int $normSetId, ?int $scaleId = null): array
    {
        if ($scaleId !== null) {
            $rows = $this->db->get_results(
                $this->db->prepare(
                    "SELECT * FROM {$this->entriesTable} WHERE norm_set_id = %d AND scale_id = %d ORDER BY raw_score_min ASC",
                    $normSetId,
                    $scaleId
                ),
                ARRAY_A
            );
        } else {
            $rows = $this->db->get_results(
                $this->db->prepare(
                    "SELECT * FROM {$this->entriesTable} WHERE norm_set_id = %d ORDER BY scale_id ASC, raw_score_min ASC",
                    $normSetId
                ),
                ARRAY_A
            );
        }

        return array_map(static fn (array $row) => NormEntry::fromRow($row), $rows ?? []);
    }

    public function insertEntry(NormEntry $entry): NormEntry
    {
        $this->db->insert($this->entriesTable, [
            'norm_set_id' => $entry->normSetId,
            'scale_id' => $entry->scaleId,
            'raw_score_min' => $entry->rawScoreMin,
            'raw_score_max' => $entry->rawScoreMax,
            'standard_score_type' => $entry->standardScoreType,
            'standard_score_value' => $entry->standardScoreValue,
            'interpretation_range_code' => $entry->interpretationRangeCode,
        ]);

        return $this->findEntry((int) $this->db->insert_id);
    }

    public function updateEntry(NormEntry $entry): void
    {
        $this->db->update(
            $this->entriesTable,
            [
                'raw_score_min' => $entry->rawScoreMin,
                'raw_score_max' => $entry->rawScoreMax,
                'standard_score_type' => $entry->standardScoreType,
                'standard_score_value' => $entry->standardScoreValue,
                'interpretation_range_code' => $entry->interpretationRangeCode,
            ],
            ['id' => $entry->id]
        );
    }

    public function deleteEntry(int $id): void
    {
        $this->db->delete($this->entriesTable, ['id' => $id]);
    }
}
