<?php

declare(strict_types=1);

namespace TpmPsych\Validation;

use TpmPsych\Domain\Scoring\ScoringRule;
use TpmPsych\Validation\Expression\ExpressionEvaluator;

/**
 * Validează `formula_definition_json` în funcție de `rule_type`, înainte de
 * a permite salvarea unei reguli de cotare. Formatele de mai jos sunt un
 * mecanism generic, tehnic — NU reguli de cotare reale ale niciunui
 * instrument; conținutul exact (ce itemi intră în ce scală, cu ce ponderi)
 * va fi introdus ulterior, per instrument, prin acest mecanism, pe baza
 * materialelor autorizate primite de la titularul licenței.
 *
 * Formate acceptate:
 *  - sum:           {"item_codes": ["Q1","Q2", ...]}
 *  - weighted_sum:  {"weights": {"Q1": 2, "Q2": 1.5, ...}}
 *  - formula:       {"expression": "Q1 + Q2 * 2 - Q3"}
 *  - conditional:   {"conditions": [{"if_item":"Q5","equals":"1","then_value":10}, ...], "default": 0}
 *  - lookup:        {"item_code": "Q1", "table": [{"match":"1","value":3}, ...]}
 */
final class FormulaDefinitionValidator
{
    public function __construct(
        private readonly ExpressionEvaluator $expressionEvaluator = new ExpressionEvaluator(),
    ) {
    }

    /**
     * @param array<string, mixed> $definition
     * @param list<string> $knownItemCodes
     */
    public function validate(string $ruleType, array $definition, array $knownItemCodes): void
    {
        if (! in_array($ruleType, ScoringRule::VALID_TYPES, true)) {
            throw new ValidationException(['Tip de regulă de cotare necunoscut: "' . $ruleType . '".']);
        }

        match ($ruleType) {
            ScoringRule::TYPE_SUM => $this->validateSum($definition, $knownItemCodes),
            ScoringRule::TYPE_WEIGHTED_SUM => $this->validateWeightedSum($definition, $knownItemCodes),
            ScoringRule::TYPE_FORMULA => $this->validateFormula($definition, $knownItemCodes),
            ScoringRule::TYPE_CONDITIONAL => $this->validateConditional($definition, $knownItemCodes),
            ScoringRule::TYPE_LOOKUP => $this->validateLookup($definition, $knownItemCodes),
        };
    }

    /** @param array<string, mixed> $definition @param list<string> $knownItemCodes */
    private function validateSum(array $definition, array $knownItemCodes): void
    {
        $itemCodes = $definition['item_codes'] ?? null;

        if (! is_array($itemCodes) || $itemCodes === []) {
            throw new ValidationException(['Regula "sum" necesită "item_codes" ca listă nevidă.']);
        }

        $this->assertKnownCodes($itemCodes, $knownItemCodes, 'item_codes');
    }

    /** @param array<string, mixed> $definition @param list<string> $knownItemCodes */
    private function validateWeightedSum(array $definition, array $knownItemCodes): void
    {
        $weights = $definition['weights'] ?? null;

        if (! is_array($weights) || $weights === []) {
            throw new ValidationException(['Regula "weighted_sum" necesită "weights" ca hartă nevidă cod_item => pondere.']);
        }

        $this->assertKnownCodes(array_keys($weights), $knownItemCodes, 'weights');

        foreach ($weights as $code => $weight) {
            if (! is_numeric($weight)) {
                throw new ValidationException(['Ponderea pentru "' . $code . '" trebuie să fie numerică.']);
            }
        }
    }

    /** @param array<string, mixed> $definition @param list<string> $knownItemCodes */
    private function validateFormula(array $definition, array $knownItemCodes): void
    {
        $expression = $definition['expression'] ?? null;

        if (! is_string($expression) || trim($expression) === '') {
            throw new ValidationException(['Regula "formula" necesită "expression" ca șir nevid.']);
        }

        try {
            $this->expressionEvaluator->validateSyntax($expression, $knownItemCodes);
        } catch (ExpressionException $e) {
            throw new ValidationException(['Formulă invalidă: ' . $e->getMessage()]);
        }
    }

    /** @param array<string, mixed> $definition @param list<string> $knownItemCodes */
    private function validateConditional(array $definition, array $knownItemCodes): void
    {
        $conditions = $definition['conditions'] ?? null;

        if (! is_array($conditions) || $conditions === []) {
            throw new ValidationException(['Regula "conditional" necesită "conditions" ca listă nevidă.']);
        }

        foreach ($conditions as $index => $condition) {
            if (! is_array($condition) || ! isset($condition['if_item'], $condition['equals'], $condition['then_value'])) {
                throw new ValidationException(['Condiția #' . $index . ' trebuie să conțină "if_item", "equals" și "then_value".']);
            }

            $this->assertKnownCodes([$condition['if_item']], $knownItemCodes, 'conditions[' . $index . '].if_item');

            if (! is_numeric($condition['then_value'])) {
                throw new ValidationException(['Condiția #' . $index . ': "then_value" trebuie să fie numeric.']);
            }
        }

        if (isset($definition['default']) && ! is_numeric($definition['default'])) {
            throw new ValidationException(['"default" trebuie să fie numeric dacă e prezent.']);
        }
    }

    /** @param array<string, mixed> $definition @param list<string> $knownItemCodes */
    private function validateLookup(array $definition, array $knownItemCodes): void
    {
        $itemCode = $definition['item_code'] ?? null;
        $table = $definition['table'] ?? null;

        if (! is_string($itemCode) || $itemCode === '') {
            throw new ValidationException(['Regula "lookup" necesită "item_code".']);
        }

        $this->assertKnownCodes([$itemCode], $knownItemCodes, 'item_code');

        if (! is_array($table) || $table === []) {
            throw new ValidationException(['Regula "lookup" necesită "table" ca listă nevidă.']);
        }

        foreach ($table as $index => $row) {
            if (! is_array($row) || ! array_key_exists('match', $row) || ! array_key_exists('value', $row)) {
                throw new ValidationException(['Rândul #' . $index . ' din "table" trebuie să conțină "match" și "value".']);
            }

            if (! is_numeric($row['value'])) {
                throw new ValidationException(['Rândul #' . $index . ' din "table": "value" trebuie să fie numeric.']);
            }
        }
    }

    /**
     * @param list<mixed> $codes
     * @param list<string> $knownItemCodes
     */
    private function assertKnownCodes(array $codes, array $knownItemCodes, string $field): void
    {
        foreach ($codes as $code) {
            if (! is_string($code) || ! in_array($code, $knownItemCodes, true)) {
                throw new ValidationException(['"' . $field . '" referă un item necunoscut în versiunea instrumentului: "' . (string) $code . '".']);
            }
        }
    }
}
