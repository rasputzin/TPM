<?php

declare(strict_types=1);

namespace TpmPsych\Services\ScoringEngine\Rules;

use TpmPsych\Services\ScoringEngine\ScoringResult;

final class ConditionalRule implements ScoringRuleStrategyInterface
{
    private const EPSILON = 1e-9;

    public function compute(array $formulaDefinition, array $itemValues): ScoringResult
    {
        $conditions = $formulaDefinition['conditions'] ?? [];
        $missing = [];

        foreach ($conditions as $condition) {
            $ifItem = (string) $condition['if_item'];

            if (! array_key_exists($ifItem, $itemValues)) {
                $missing[] = $ifItem;
                continue;
            }

            if (abs($itemValues[$ifItem] - (float) $condition['equals']) < self::EPSILON) {
                return ScoringResult::computed((float) $condition['then_value']);
            }
        }

        if ($missing !== []) {
            return ScoringResult::incomplete($missing);
        }

        if (isset($formulaDefinition['default'])) {
            return ScoringResult::computed((float) $formulaDefinition['default']);
        }

        return ScoringResult::computed(0.0, [
            'Nicio condiție nu s-a potrivit și nu există valoare implicită ("default") definită; s-a folosit 0.',
        ]);
    }
}
