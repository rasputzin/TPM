<?php

declare(strict_types=1);

namespace TpmPsych\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use TpmPsych\Validation\Expression\ExpressionEvaluator;
use TpmPsych\Validation\ExpressionException;

final class ExpressionEvaluatorTest extends TestCase
{
    private ExpressionEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new ExpressionEvaluator();
    }

    public function testAddsAndSubtractsVariables(): void
    {
        $result = $this->evaluator->evaluate('Q1 + Q2 - Q3', ['Q1' => 3.0, 'Q2' => 5.0, 'Q3' => 2.0]);

        self::assertSame(6.0, $result);
    }

    public function testRespectsOperatorPrecedence(): void
    {
        $result = $this->evaluator->evaluate('Q1 + Q2 * 2', ['Q1' => 1.0, 'Q2' => 3.0]);

        self::assertSame(7.0, $result);
    }

    public function testRespectsParentheses(): void
    {
        $result = $this->evaluator->evaluate('(Q1 + Q2) * 2', ['Q1' => 1.0, 'Q2' => 3.0]);

        self::assertSame(8.0, $result);
    }

    public function testSupportsUnaryMinus(): void
    {
        $result = $this->evaluator->evaluate('-Q1 + 10', ['Q1' => 4.0]);

        self::assertSame(6.0, $result);
    }

    public function testDivision(): void
    {
        $result = $this->evaluator->evaluate('Q1 / 4', ['Q1' => 8.0]);

        self::assertSame(2.0, $result);
    }

    public function testDivisionByZeroThrows(): void
    {
        $this->expectException(ExpressionException::class);

        $this->evaluator->evaluate('Q1 / 0', ['Q1' => 8.0]);
    }

    public function testUnknownVariableThrows(): void
    {
        $this->expectException(ExpressionException::class);
        $this->expectExceptionMessage('necunoscută');

        $this->evaluator->evaluate('Q1 + Q99', ['Q1' => 1.0]);
    }

    /**
     * Cerință de securitate centrală: niciun caracter din afara whitelist-ului
     * (litere/cifre/underscore/operatori aritmetici/paranteze/spațiu) nu e
     * acceptat — altfel o "formulă" de cotare ar putea deveni un vector de
     * injectare de cod în configurarea instrumentelor.
     */
    public function testRejectsDisallowedCharacters(): void
    {
        $this->expectException(ExpressionException::class);

        $this->evaluator->evaluate('system("rm -rf /")', []);
    }

    public function testRejectsSemicolonInjectionAttempt(): void
    {
        $this->expectException(ExpressionException::class);

        $this->evaluator->evaluate('Q1; phpinfo()', ['Q1' => 1.0]);
    }

    public function testValidateSyntaxPassesWithKnownVariables(): void
    {
        $this->evaluator->validateSyntax('Q1 + Q2', ['Q1', 'Q2']);

        self::assertTrue(true);
    }

    public function testValidateSyntaxFailsWithUnknownVariables(): void
    {
        $this->expectException(ExpressionException::class);

        $this->evaluator->validateSyntax('Q1 + Q99', ['Q1']);
    }
}
