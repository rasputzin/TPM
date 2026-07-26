<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Wpdb;

use TpmPsych\Domain\Scoring\ScoringRule;
use TpmPsych\Repositories\Contracts\ScoringRuleRepositoryInterface;

final class WpdbScoringRuleRepository implements ScoringRuleRepositoryInterface
{
    private readonly \wpdb $db;

    private readonly string $table;

    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
        $this->table = $this->db->prefix . 'tpm_scoring_rules';
    }

    public function find(int $id): ?ScoringRule
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id), ARRAY_A);

        return $row !== null ? ScoringRule::fromRow($row) : null;
    }

    public function findActiveByScale(int $scaleId): ?ScoringRule
    {
        $row = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE scale_id = %d AND status = 'active' LIMIT 1", $scaleId),
            ARRAY_A
        );

        return $row !== null ? ScoringRule::fromRow($row) : null;
    }

    public function listByScale(int $scaleId): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare("SELECT * FROM {$this->table} WHERE scale_id = %d ORDER BY id DESC", $scaleId),
            ARRAY_A
        );

        return array_map(static fn (array $row) => ScoringRule::fromRow($row), $rows ?? []);
    }

    public function insert(ScoringRule $rule): ScoringRule
    {
        $now = current_time('mysql', true);

        $this->db->insert($this->table, [
            'scale_id' => $rule->scaleId,
            'rule_type' => $rule->ruleType,
            'formula_definition_json' => wp_json_encode($rule->formulaDefinition),
            'engine_version' => $rule->engineVersion,
            'status' => $rule->status,
            'created_at' => $now,
        ]);

        return $this->find((int) $this->db->insert_id);
    }

    public function update(ScoringRule $rule): void
    {
        $this->db->update(
            $this->table,
            [
                'rule_type' => $rule->ruleType,
                'formula_definition_json' => wp_json_encode($rule->formulaDefinition),
                'engine_version' => $rule->engineVersion,
                'status' => $rule->status,
                'updated_at' => current_time('mysql', true),
            ],
            ['id' => $rule->id]
        );
    }
}
