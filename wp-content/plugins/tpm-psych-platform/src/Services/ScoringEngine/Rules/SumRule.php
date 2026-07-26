<?php

declare(strict_types=1);

namespace TpmPsych\Services\ScoringEngine\Rules;

use TpmPsych\Services\ScoringEngine\ScoringResult;

final class SumRule implements ScoringRuleStrategyInterface
{
    public function compute(array $formulaDefinition, array $itemValues): ScoringResult
    {
        $itemCodes = $formulaDefinition['item_codes'] ?? [];

        $sum = 0.0;
        $missing = [];

        foreach ($itemCodes as $code) {
            if (! array_key_exists($code, $itemValues)) {
                $missing[] = $code;
                continue;
            }
            $sum += $itemValues[$code];
        }

        if ($missing !== []) {
            // Politica exactă pentru răspunsuri lipsă (imputare, prag de invaliditate
            // etc.) e specifică fiecărui instrument și se aplică la nivelul
            // orchestratorului de cotare, pe baza `missing_response_policy_json` a
            // versiunii instrumentului — motorul de bază raportează doar faptul brut.
            return ScoringResult::incomplete($missing);
        }

        return ScoringResult::computed($sum);
    }
}
