<?php

declare(strict_types=1);

namespace TpmPsych\Tests\Fixtures;

use TpmPsych\Domain\Scoring\ScoringRule;
use TpmPsych\Repositories\Contracts\ScoringRuleRepositoryInterface;

final class InMemoryScoringRuleRepository implements ScoringRuleRepositoryInterface
{
    /** @var array<int, ScoringRule> */
    private array $rows = [];

    private int $nextId = 1;

    public function find(int $id): ?ScoringRule
    {
        return $this->rows[$id] ?? null;
    }

    public function findActiveByScale(int $scaleId): ?ScoringRule
    {
        foreach ($this->rows as $row) {
            if ($row->scaleId === $scaleId && $row->status === 'active') {
                return $row;
            }
        }

        return null;
    }

    public function listByScale(int $scaleId): array
    {
        return array_values(array_filter($this->rows, static fn (ScoringRule $r) => $r->scaleId === $scaleId));
    }

    public function insert(ScoringRule $rule): ScoringRule
    {
        $id = $this->nextId++;
        $created = new ScoringRule($id, $rule->scaleId, $rule->ruleType, $rule->formulaDefinition, $rule->engineVersion, $rule->status);
        $this->rows[$id] = $created;

        return $created;
    }

    public function update(ScoringRule $rule): void
    {
        $this->rows[$rule->id] = $rule;
    }
}
