<?php

declare(strict_types=1);

namespace TpmPsych\Validation\Expression;

use TpmPsych\Validation\ExpressionException;

/**
 * Evaluator recursive-descent pentru formule de cotare de tip aritmetic
 * simplu (+ - * / paranteze, variabile numite = coduri de item/scală).
 *
 * Deliberat NU folosește eval()/create_function()/call_user_func cu input
 * din configurare: formulele de cotare sunt introduse de administratorul de
 * platformă prin `tpm_scoring_rules.formula_definition_json`, deci trebuie
 * tratate ca input de configurare, nu ca fiind de încredere implicit —
 * a le executa ca PHP arbitrar ar fi o vulnerabilitate de execuție de cod
 * la distanță (RCE) din propria bază de date.
 *
 * Grammar:
 *   expression := term (('+'|'-') term)*
 *   term       := factor (('*'|'/') factor)*
 *   factor     := ('-'|'+') factor | primary
 *   primary    := NUMBER | IDENT | '(' expression ')'
 */
final class ExpressionEvaluator
{
    /** @var list<ExpressionToken> */
    private array $tokens;

    private int $position = 0;

    /** @param array<string, float> $variables */
    public function evaluate(string $expression, array $variables): float
    {
        $this->tokens = (new ExpressionTokenizer())->tokenize($expression);
        $this->position = 0;

        $result = $this->parseExpression($variables);

        if ($this->current()->type !== ExpressionToken::EOF) {
            throw new ExpressionException('Token neașteptat la finalul formulei: "' . $this->current()->value . '".');
        }

        return $result;
    }

    /**
     * Verifică doar validitatea sintactică + faptul că toate variabilele
     * referite există în $knownVariableNames, fără a calcula o valoare
     * reală — folosit la salvarea unei reguli de cotare, înainte de a avea
     * răspunsuri de evaluat.
     *
     * @param list<string> $knownVariableNames
     */
    public function validateSyntax(string $expression, array $knownVariableNames): void
    {
        $probeVariables = array_fill_keys($knownVariableNames, 0.0);
        $this->evaluate($expression, $probeVariables);
    }

    /** @param array<string, float> $variables */
    private function parseExpression(array $variables): float
    {
        $value = $this->parseTerm($variables);

        while (in_array($this->current()->type, [ExpressionToken::PLUS, ExpressionToken::MINUS], true)) {
            $operator = $this->advance()->type;
            $right = $this->parseTerm($variables);
            $value = $operator === ExpressionToken::PLUS ? $value + $right : $value - $right;
        }

        return $value;
    }

    /** @param array<string, float> $variables */
    private function parseTerm(array $variables): float
    {
        $value = $this->parseFactor($variables);

        while (in_array($this->current()->type, [ExpressionToken::STAR, ExpressionToken::SLASH], true)) {
            $operator = $this->advance()->type;
            $right = $this->parseFactor($variables);

            if ($operator === ExpressionToken::SLASH) {
                if ($right === 0.0) {
                    throw new ExpressionException('Împărțire la zero în formula de cotare.');
                }
                $value /= $right;
            } else {
                $value *= $right;
            }
        }

        return $value;
    }

    /** @param array<string, float> $variables */
    private function parseFactor(array $variables): float
    {
        if ($this->current()->type === ExpressionToken::MINUS) {
            $this->advance();
            return -$this->parseFactor($variables);
        }

        if ($this->current()->type === ExpressionToken::PLUS) {
            $this->advance();
            return $this->parseFactor($variables);
        }

        return $this->parsePrimary($variables);
    }

    /** @param array<string, float> $variables */
    private function parsePrimary(array $variables): float
    {
        $token = $this->current();

        if ($token->type === ExpressionToken::NUMBER) {
            $this->advance();
            return (float) $token->value;
        }

        if ($token->type === ExpressionToken::IDENT) {
            $this->advance();
            if (! array_key_exists($token->value, $variables)) {
                throw new ExpressionException('Variabilă necunoscută în formula de cotare: "' . $token->value . '".');
            }
            return $variables[$token->value];
        }

        if ($token->type === ExpressionToken::LPAREN) {
            $this->advance();
            $value = $this->parseExpression($variables);
            $this->expect(ExpressionToken::RPAREN);
            return $value;
        }

        throw new ExpressionException('Expresie invalidă în formula de cotare lângă "' . $token->value . '".');
    }

    private function current(): ExpressionToken
    {
        return $this->tokens[$this->position];
    }

    private function advance(): ExpressionToken
    {
        return $this->tokens[$this->position++];
    }

    private function expect(string $type): ExpressionToken
    {
        if ($this->current()->type !== $type) {
            throw new ExpressionException('Se aștepta "' . $type . '" în formula de cotare, s-a găsit "' . $this->current()->value . '".');
        }

        return $this->advance();
    }
}
