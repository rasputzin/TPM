<?php

declare(strict_types=1);

namespace TpmPsych\Services\ScoringEngine\Rules;

use TpmPsych\Services\ScoringEngine\ScoringResult;

final class WeightedSumRule implements ScoringRuleStrategyInterface
{
    public function compute(array $formulaDefinition, array $itemValues): ScoringResult
    {
        $weights = $formulaDefinition['weights'] ?? [];

        $sum = 0.0;
        $missing = [];

        foreach ($weights as $code => $weight) {
            if (! array_key_exists($code, $itemValues)) {
                $missing[] = $code;
                continue;
            }
            $sum += $itemValues[$code] * (float) $weight;
        }

        if ($missing !== []) {
            return ScoringResult::incomplete($missing);
        }

        return ScoringResult::computed($sum);
    }
}
