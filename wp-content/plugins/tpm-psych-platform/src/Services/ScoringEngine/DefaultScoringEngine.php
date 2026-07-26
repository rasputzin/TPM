<?php

declare(strict_types=1);

namespace TpmPsych\Services\ScoringEngine;

use TpmPsych\Domain\Scoring\ScoringRule;
use TpmPsych\Services\ScoringEngine\Rules\ConditionalRule;
use TpmPsych\Services\ScoringEngine\Rules\FormulaRule;
use TpmPsych\Services\ScoringEngine\Rules\LookupRule;
use TpmPsych\Services\ScoringEngine\Rules\ScoringRuleStrategyInterface;
use TpmPsych\Services\ScoringEngine\Rules\SumRule;
use TpmPsych\Services\ScoringEngine\Rules\WeightedSumRule;

/**
 * Motor de cotare pur (fără dependențe de WordPress/DB) — primește regula și
 * valorile brute de răspuns deja rezolvate, întoarce un scor. Rulează
 * exclusiv server-side, apelat din Service Layer după autorizare, niciodată
 * expus direct unui endpoint accesibil din frontend-ul respondentului.
 */
final class DefaultScoringEngine implements ScoringEngineInterface
{
    /** @var array<string, ScoringRuleStrategyInterface> */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            ScoringRule::TYPE_SUM => new SumRule(),
            ScoringRule::TYPE_WEIGHTED_SUM => new WeightedSumRule(),
            ScoringRule::TYPE_FORMULA => new FormulaRule(),
            ScoringRule::TYPE_CONDITIONAL => new ConditionalRule(),
            ScoringRule::TYPE_LOOKUP => new LookupRule(),
        ];
    }

    public function computeScaleScore(ScoringRule $rule, array $itemValues): ScoringResult
    {
        $strategy = $this->strategies[$rule->ruleType] ?? null;

        if ($strategy === null) {
            throw new \RuntimeException('Nicio strategie de cotare înregistrată pentru tipul "' . $rule->ruleType . '".');
        }

        return $strategy->compute($rule->formulaDefinition, $itemValues);
    }
}
