<?php

declare(strict_types=1);

namespace TpmPsych\Tests\Unit\ScoringEngine;

use PHPUnit\Framework\TestCase;
use TpmPsych\Domain\Scoring\NormEntry;
use TpmPsych\Services\ScoringEngine\NormLookup;

final class NormLookupTest extends TestCase
{
    private NormLookup $lookup;

    /** @var list<NormEntry> */
    private array $entries;

    protected function setUp(): void
    {
        $this->lookup = new NormLookup();

        // Etalon demonstrativ, fabricat — nu corespunde niciunui instrument real.
        $this->entries = [
            new NormEntry(1, 1, 1, 0.0, 5.0, NormEntry::TYPE_T_SCORE, 40.0, 'low'),
            new NormEntry(2, 1, 1, 6.0, 10.0, NormEntry::TYPE_T_SCORE, 55.0, 'average'),
            new NormEntry(3, 1, 1, 11.0, 20.0, NormEntry::TYPE_T_SCORE, 70.0, 'high'),
        ];
    }

    public function testFindsEntryContainingScoreAtLowerBound(): void
    {
        $entry = $this->lookup->find($this->entries, 6.0);

        self::assertNotNull($entry);
        self::assertSame('average', $entry->interpretationRangeCode);
    }

    public function testFindsEntryContainingScoreAtUpperBound(): void
    {
        $entry = $this->lookup->find($this->entries, 10.0);

        self::assertNotNull($entry);
        self::assertSame('average', $entry->interpretationRangeCode);
    }

    public function testReturnsNullWhenScoreOutOfRange(): void
    {
        $entry = $this->lookup->find($this->entries, 999.0);

        self::assertNull($entry);
    }

    public function testReturnsNullForEmptyEntryList(): void
    {
        self::assertNull($this->lookup->find([], 5.0));
    }
}
