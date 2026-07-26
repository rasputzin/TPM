<?php

declare(strict_types=1);

namespace TpmPsych\Services\ScoringEngine;

use TpmPsych\Domain\Scoring\ScoringRule;

interface ScoringEngineInterface
{
    /** @param array<string, float> $itemValues */
    public function computeScaleScore(ScoringRule $rule, array $itemValues): ScoringResult;
}
