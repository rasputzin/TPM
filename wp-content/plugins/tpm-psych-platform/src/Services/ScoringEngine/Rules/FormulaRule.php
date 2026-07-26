<?php

declare(strict_types=1);

namespace TpmPsych\Services\ScoringEngine\Rules;

use TpmPsych\Services\ScoringEngine\ScoringResult;
use TpmPsych\Validation\Expression\ExpressionEvaluator;
use TpmPsych\Validation\ExpressionException;

final class FormulaRule implements ScoringRuleStrategyInterface
{
    public function __construct(
        private readonly ExpressionEvaluator $evaluator = new ExpressionEvaluator(),
    ) {
    }

    public function compute(array $formulaDefinition, array $itemValues): ScoringResult
    {
        $expression = (string) ($formulaDefinition['expression'] ?? '');

        try {
            $value = $this->evaluator->evaluate($expression, $itemValues);
        } catch (ExpressionException $e) {
            // O variabilă necunoscută la evaluare (spre deosebire de validarea la
            // salvare, care verifică doar codurile de item existente) înseamnă aici
            // un item din formulă fără răspuns înregistrat — protocol incomplet.
            return ScoringResult::incomplete(
                $this->extractMissingVariable($e->getMessage(), $formulaDefinition, $itemValues)
            );
        }

        return ScoringResult::computed($value);
    }

    /**
     * @param array<string, mixed> $formulaDefinition
     * @param array<string, float> $itemValues
     * @return list<string>
     */
    private function extractMissingVariable(string $message, array $formulaDefinition, array $itemValues): array
    {
        if (! str_contains($message, 'Variabilă necunoscută')) {
            return ['expression'];
        }

        if (preg_match('/"([^"]+)"/', $message, $matches) === 1) {
            return [$matches[1]];
        }

        return ['expression'];
    }
}
