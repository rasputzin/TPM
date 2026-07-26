<?php

declare(strict_types=1);

namespace TpmPsych\Services\ScoringEngine\Rules;

use TpmPsych\Services\ScoringEngine\ScoringResult;

final class LookupRule implements ScoringRuleStrategyInterface
{
    private const EPSILON = 1e-9;

    public function compute(array $formulaDefinition, array $itemValues): ScoringResult
    {
        $itemCode = (string) $formulaDefinition['item_code'];

        if (! array_key_exists($itemCode, $itemValues)) {
            return ScoringResult::incomplete([$itemCode]);
        }

        $value = $itemValues[$itemCode];

        foreach ($formulaDefinition['table'] as $row) {
            if (abs($value - (float) $row['match']) < self::EPSILON) {
                return ScoringResult::computed((float) $row['value']);
            }
        }

        return ScoringResult::computed(0.0, [
            'Valoarea răspunsului nu se regăsește în tabelul de conversie; s-a folosit 0.',
        ]);
    }
}
