<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Wpdb;

use TpmPsych\Domain\Report\ReportTemplate;
use TpmPsych\Repositories\Contracts\ReportTemplateRepositoryInterface;

final class WpdbReportTemplateRepository implements ReportTemplateRepositoryInterface
{
    private readonly \wpdb $db;

    private readonly string $table;

    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
        $this->table = $this->db->prefix . 'tpm_report_templates';
    }

    public function find(int $id): ?ReportTemplate
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);

        return $row !== null ? ReportTemplate::fromRow($row) : null;
    }

    public function findActiveForInstrument(int $organizationId, ?int $instrumentId): ?ReportTemplate
    {
        if ($instrumentId !== null) {
            $row = $this->db->get_row(
                $this->db->prepare(
                    "SELECT * FROM {$this->table} WHERE organization_id = %d AND instrument_id = %d AND status = 'active' LIMIT 1",
                    $organizationId,
                    $instrumentId
                ),
                ARRAY_A
            );

            if ($row !== null) {
                return ReportTemplate::fromRow($row);
            }
        }

        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE organization_id = %d AND instrument_id IS NULL AND status = 'active' LIMIT 1",
                $organizationId
            ),
            ARRAY_A
        );

        return $row !== null ? ReportTemplate::fromRow($row) : null;
    }

    public function listByOrganization(int $organizationId): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE organization_id = %d ORDER BY id DESC", $organizationId),
            ARRAY_A
        );

        return array_map(static fn (array $row) => ReportTemplate::fromRow($row), $rows ?? []);
    }

    public function insert(ReportTemplate $template, int $actorUserId): ReportTemplate
    {
        $now = current_time('mysql', true);

        $this->db->insert($this->table, [
            'organization_id' => $template->organizationId,
            'instrument_id' => $template->instrumentId,
            'version_label' => $template->versionLabel,
            'layout_definition_json' => wp_json_encode($template->layoutDefinition),
            'status' => $template->status,
            'created_at' => $now,
            'created_by' => $actorUserId,
        ]);

        return $this->find((int) $this->db->insert_id);
    }

    public function update(ReportTemplate $template, int $actorUserId): void
    {
        $this->db->update(
            $this->table,
            [
                'layout_definition_json' => wp_json_encode($template->layoutDefinition),
                'status' => $template->status,
                'updated_at' => current_time('mysql', true),
                'updated_by' => $actorUserId,
            ],
            ['id' => $template->id]
        );
    }
}
