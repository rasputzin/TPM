<?php

declare(strict_types=1);

namespace TpmPsych\Services\ScoringEngine\Rules;

use TpmPsych\Services\ScoringEngine\ScoringResult;

interface ScoringRuleStrategyInterface
{
    /**
     * @param array<string, mixed> $formulaDefinition validat deja de FormulaDefinitionValidator la salvare
     * @param array<string, float> $itemValues cod item => valoare brută a răspunsului (deja "cheiată"/inversată la configurare, dacă e cazul)
     */
    public function compute(array $formulaDefinition, array $itemValues): ScoringResult;
}
