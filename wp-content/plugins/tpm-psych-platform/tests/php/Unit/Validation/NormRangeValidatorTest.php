<?php

declare(strict_types=1);

namespace TpmPsych\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use TpmPsych\Domain\Scoring\NormEntry;
use TpmPsych\Validation\NormRangeValidator;
use TpmPsych\Validation\ValidationException;

final class NormRangeValidatorTest extends TestCase
{
    private NormRangeValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new NormRangeValidator();
    }

    public function testAcceptsAdjacentNonOverlappingRanges(): void
    {
        $existing = [new NormEntry(1, 1, 1, 0.0, 5.0, NormEntry::TYPE_T_SCORE, 40.0, 'low')];
        $candidate = new NormEntry(null, 1, 1, 6.0, 10.0, NormEntry::TYPE_T_SCORE, 55.0, 'average');

        $this->validator->assertNoOverlap($candidate, $existing);

        self::assertTrue(true);
    }

    public function testRejectsOverlappingRanges(): void
    {
        $existing = [new NormEntry(1, 1, 1, 0.0, 5.0, NormEntry::TYPE_T_SCORE, 40.0, 'low')];
        $candidate = new NormEntry(null, 1, 1, 5.0, 10.0, NormEntry::TYPE_T_SCORE, 55.0, 'average');

        $this->expectException(ValidationException::class);
        $this->validator->assertNoOverlap($candidate, $existing);
    }

    public function testRejectsInvertedRange(): void
    {
        $candidate = new NormEntry(null, 1, 1, 10.0, 5.0, NormEntry::TYPE_T_SCORE, 55.0, 'average');

        $this->expectException(ValidationException::class);
        $this->validator->assertNoOverlap($candidate, []);
    }

    public function testFindGapsDetectsUncoveredInterval(): void
    {
        $entries = [
            new NormEntry(1, 1, 1, 0.0, 5.0, NormEntry::TYPE_T_SCORE, 40.0, 'low'),
            new NormEntry(2, 1, 1, 8.0, 10.0, NormEntry::TYPE_T_SCORE, 55.0, 'average'),
        ];

        $gaps = $this->validator->findGaps($entries);

        self::assertNotEmpty($gaps);
    }

    public function testFindGapsReturnsEmptyForContiguousEntries(): void
    {
        $entries = [
            new NormEntry(1, 1, 1, 0.0, 5.0, NormEntry::TYPE_T_SCORE, 40.0, 'low'),
            new NormEntry(2, 1, 1, 5.0, 10.0, NormEntry::TYPE_T_SCORE, 55.0, 'average'),
        ];

        self::assertSame([], $this->validator->findGaps($entries));
    }
}
