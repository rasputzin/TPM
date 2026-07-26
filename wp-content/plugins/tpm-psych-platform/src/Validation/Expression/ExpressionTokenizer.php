<?php

declare(strict_types=1);

namespace TpmPsych\Validation\Expression;

use TpmPsych\Validation\ExpressionException;

/**
 * Tokenizer strict cu whitelist de caractere. Orice caracter care nu
 * corespunde unui token cunoscut (cifre, litere/underscore, . + - * / ( ),
 * spațiu) oprește parsarea cu eroare — nu există niciun mod de a strecura
 * cod PHP sau alt conținut executabil printr-o "formulă" de cotare.
 */
final class ExpressionTokenizer
{
    /** @return list<ExpressionToken> */
    public function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $i = 0;

        while ($i < $length) {
            $char = $expression[$i];

            if (ctype_space($char)) {
                $i++;
                continue;
            }

            if ($char === '+') {
                $tokens[] = new ExpressionToken(ExpressionToken::PLUS, '+');
                $i++;
                continue;
            }

            if ($char === '-') {
                $tokens[] = new ExpressionToken(ExpressionToken::MINUS, '-');
                $i++;
                continue;
            }

            if ($char === '*') {
                $tokens[] = new ExpressionToken(ExpressionToken::STAR, '*');
                $i++;
                continue;
            }

            if ($char === '/') {
                $tokens[] = new ExpressionToken(ExpressionToken::SLASH, '/');
                $i++;
                continue;
            }

            if ($char === '(') {
                $tokens[] = new ExpressionToken(ExpressionToken::LPAREN, '(');
                $i++;
                continue;
            }

            if ($char === ')') {
                $tokens[] = new ExpressionToken(ExpressionToken::RPAREN, ')');
                $i++;
                continue;
            }

            if (ctype_digit($char)) {
                $start = $i;
                $seenDot = false;
                while ($i < $length && (ctype_digit($expression[$i]) || ($expression[$i] === '.' && ! $seenDot))) {
                    if ($expression[$i] === '.') {
                        $seenDot = true;
                    }
                    $i++;
                }
                $tokens[] = new ExpressionToken(ExpressionToken::NUMBER, substr($expression, $start, $i - $start));
                continue;
            }

            if (ctype_alpha($char) || $char === '_') {
                $start = $i;
                while ($i < $length && (ctype_alnum($expression[$i]) || $expression[$i] === '_')) {
                    $i++;
                }
                $tokens[] = new ExpressionToken(ExpressionToken::IDENT, substr($expression, $start, $i - $start));
                continue;
            }

            throw new ExpressionException(sprintf('Caracter neautorizat în formulă la poziția %d: "%s".', $i, $char));
        }

        $tokens[] = new ExpressionToken(ExpressionToken::EOF, '');

        return $tokens;
    }
}
