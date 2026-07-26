<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Contracts;

use TpmPsych\Domain\Scoring\ScoringRule;

interface ScoringRuleRepositoryInterface
{
    public function find(int $id): ?ScoringRule;

    public function findActiveByScale(int $scaleId): ?ScoringRule;

    /** @return list<ScoringRule> */
    public function listByScale(int $scaleId): array;

    public function insert(ScoringRule $rule): ScoringRule;

    public function update(ScoringRule $rule): void;
}
