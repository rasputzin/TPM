<?php

declare(strict_types=1);

namespace TpmPsych\Tests\Unit\Services\Report;

use PHPUnit\Framework\TestCase;
use TpmPsych\Domain\Instrument\Scale;
use TpmPsych\Domain\Report\ReportTemplate;
use TpmPsych\Domain\Scoring\NormEntry;
use TpmPsych\Services\Report\ReportContentResolver;
use TpmPsych\Services\ScoringEngine\ScoringResult;

/**
 * Test demonstrativ, cu date fabricate: verifică lanțul complet
 * scor -> interval de etalon -> text autorizat din șablon, exact mecanismul
 * cerut pentru a putea introduce ulterior chei de cotare + etaloane +
 * șabloane de raport reale, per instrument.
 */
final class ReportContentResolverTest extends TestCase
{
    public function testResolvesInterpretationTextForComputedScore(): void
    {
        $resolver = new ReportContentResolver();

        $scale = new Scale(1, 1, null, 'TOTAL', 'Scală demonstrativă');

        $normEntries = [
            new NormEntry(1, 1, 1, 0.0, 5.0, NormEntry::TYPE_T_SCORE, 40.0, 'low'),
            new NormEntry(2, 1, 1, 6.0, 15.0, NormEntry::TYPE_T_SCORE, 60.0, 'high'),
        ];

        $template = new ReportTemplate(1, 1, null, '1.0.0', [
            'interpretation_texts' => [
                ['interpretation_range_code' => 'low', 'title' => 'Nivel scăzut', 'body' => 'Text autorizat demonstrativ pentru nivel scăzut.'],
                ['interpretation_range_code' => 'high', 'title' => 'Nivel ridicat', 'body' => 'Text autorizat demonstrativ pentru nivel ridicat.'],
            ],
        ], 'active');

        $sections = $resolver->resolve(
            ['TOTAL' => $scale],
            ['TOTAL' => ScoringResult::computed(10.0)],
            ['TOTAL' => $normEntries],
            $template
        );

        self::assertCount(1, $sections);
        self::assertTrue($sections[0]->isInterpretable());
        self::assertSame('high', $sections[0]->interpretationRangeCode);
        self::assertSame('Text autorizat demonstrativ pentru nivel ridicat.', $sections[0]->interpretationBody);
        self::assertSame(60.0, $sections[0]->standardScoreValue);
    }

    public function testMarksSectionAsNonInterpretableWhenProtocolIncomplete(): void
    {
        $resolver = new ReportContentResolver();
        $scale = new Scale(1, 1, null, 'TOTAL', 'Scală demonstrativă');
        $template = new ReportTemplate(1, 1, null, '1.0.0', [
            'interpretation_texts' => [
                ['interpretation_range_code' => 'low', 'body' => 'Text.'],
            ],
        ], 'active');

        $sections = $resolver->resolve(
            ['TOTAL' => $scale],
            ['TOTAL' => ScoringResult::incomplete(['D3'])],
            ['TOTAL' => []],
            $template
        );

        self::assertFalse($sections[0]->isInterpretable());
        self::assertNotEmpty($sections[0]->warnings);
    }

    public function testWarnsWhenScoreOutsideAllNormRanges(): void
    {
        $resolver = new ReportContentResolver();
        $scale = new Scale(1, 1, null, 'TOTAL', 'Scală demonstrativă');
        $normEntries = [new NormEntry(1, 1, 1, 0.0, 5.0, NormEntry::TYPE_T_SCORE, 40.0, 'low')];
        $template = new ReportTemplate(1, 1, null, '1.0.0', [
            'interpretation_texts' => [['interpretation_range_code' => 'low', 'body' => 'Text.']],
        ], 'active');

        $sections = $resolver->resolve(
            ['TOTAL' => $scale],
            ['TOTAL' => ScoringResult::computed(999.0)],
            ['TOTAL' => $normEntries],
            $template
        );

        self::assertFalse($sections[0]->isInterpretable());
        self::assertSame(999.0, $sections[0]->rawScore);
        self::assertNotEmpty($sections[0]->warnings);
    }
}
