<?php

declare(strict_types=1);

namespace TpmPsych\Tests\Unit\ScoringEngine;

use PHPUnit\Framework\TestCase;
use TpmPsych\Domain\Scoring\ScoringRule;
use TpmPsych\Services\ScoringEngine\DefaultScoringEngine;

/**
 * Cazuri de test cu date DEMONSTRATIVE, fabricate — nu conțin și nu se
 * bazează pe niciun item/formulă reală a vreunui instrument licențiat
 * (BDEFS, MCMI-3 sau altul). Scopul e validarea mecanismului generic de
 * cotare, conform cerinței de a nu inventa conținut psihometric real.
 */
final class DefaultScoringEngineTest extends TestCase
{
    private DefaultScoringEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new DefaultScoringEngine();
    }

    public function testSumRuleComputesTotal(): void
    {
        $rule = new ScoringRule(1, 1, ScoringRule::TYPE_SUM, ['item_codes' => ['D1', 'D2', 'D3']], '1.0.0', 'active');

        $result = $this->engine->computeScaleScore($rule, ['D1' => 2.0, 'D2' => 3.0, 'D3' => 1.0]);

        self::assertSame(6.0, $result->rawScore);
        self::assertTrue($result->isComplete());
    }

    public function testSumRuleFlagsMissingItemsAsIncomplete(): void
    {
        $rule = new ScoringRule(1, 1, ScoringRule::TYPE_SUM, ['item_codes' => ['D1', 'D2', 'D3']], '1.0.0', 'active');

        $result = $this->engine->computeScaleScore($rule, ['D1' => 2.0, 'D2' => 3.0]);

        self::assertNull($result->rawScore);
        self::assertSame(['D3'], $result->missingItemCodes);
        self::assertFalse($result->isComplete());
    }

    public function testWeightedSumRuleAppliesWeights(): void
    {
        $rule = new ScoringRule(1, 1, ScoringRule::TYPE_WEIGHTED_SUM, ['weights' => ['D1' => 2.0, 'D2' => 0.5]], '1.0.0', 'active');

        $result = $this->engine->computeScaleScore($rule, ['D1' => 3.0, 'D2' => 4.0]);

        self::assertSame(8.0, $result->rawScore);
    }

    public function testFormulaRuleEvaluatesExpression(): void
    {
        $rule = new ScoringRule(1, 1, ScoringRule::TYPE_FORMULA, ['expression' => '(D1 + D2) * 2 - D3'], '1.0.0', 'active');

        $result = $this->engine->computeScaleScore($rule, ['D1' => 1.0, 'D2' => 2.0, 'D3' => 1.0]);

        self::assertSame(5.0, $result->rawScore);
    }

    public function testFormulaRuleFlagsMissingVariableAsIncomplete(): void
    {
        $rule = new ScoringRule(1, 1, ScoringRule::TYPE_FORMULA, ['expression' => 'D1 + D2'], '1.0.0', 'active');

        $result = $this->engine->computeScaleScore($rule, ['D1' => 1.0]);

        self::assertNull($result->rawScore);
        self::assertSame(['D2'], $result->missingItemCodes);
    }

    public function testConditionalRulePicksFirstMatchingCondition(): void
    {
        $rule = new ScoringRule(1, 1, ScoringRule::TYPE_CONDITIONAL, [
            'conditions' => [
                ['if_item' => 'D1', 'equals' => 1, 'then_value' => 10],
                ['if_item' => 'D1', 'equals' => 0, 'then_value' => 0],
            ],
            'default' => -1,
        ], '1.0.0', 'active');

        $result = $this->engine->computeScaleScore($rule, ['D1' => 0.0]);

        self::assertSame(0.0, $result->rawScore);
    }

    public function testConditionalRuleFallsBackToDefault(): void
    {
        $rule = new ScoringRule(1, 1, ScoringRule::TYPE_CONDITIONAL, [
            'conditions' => [
                ['if_item' => 'D1', 'equals' => 1, 'then_value' => 10],
            ],
            'default' => -1,
        ], '1.0.0', 'active');

        $result = $this->engine->computeScaleScore($rule, ['D1' => 5.0]);

        self::assertSame(-1.0, $result->rawScore);
    }

    public function testLookupRuleFindsMatchingTableRow(): void
    {
        $rule = new ScoringRule(1, 1, ScoringRule::TYPE_LOOKUP, [
            'item_code' => 'D1',
            'table' => [
                ['match' => 0, 'value' => 0],
                ['match' => 1, 'value' => 5],
                ['match' => 2, 'value' => 10],
            ],
        ], '1.0.0', 'active');

        $result = $this->engine->computeScaleScore($rule, ['D1' => 2.0]);

        self::assertSame(10.0, $result->rawScore);
    }

    public function testLookupRuleFlagsMissingItemAsIncomplete(): void
    {
        $rule = new ScoringRule(1, 1, ScoringRule::TYPE_LOOKUP, [
            'item_code' => 'D1',
            'table' => [['match' => 0, 'value' => 0]],
        ], '1.0.0', 'active');

        $result = $this->engine->computeScaleScore($rule, []);

        self::assertNull($result->rawScore);
        self::assertSame(['D1'], $result->missingItemCodes);
    }
}
