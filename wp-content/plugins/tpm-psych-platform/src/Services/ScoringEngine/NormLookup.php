<?php

declare(strict_types=1);

namespace TpmPsych\Services\ScoringEngine;

use TpmPsych\Domain\Scoring\NormEntry;

/**
 * Conversia unui scor brut într-un scor standardizat (percentilă, T, Z,
 * stanine etc.) pe baza intrărilor unui etalon activ. Pur, fără DB — lista
 * de intrări e furnizată de apelant (deja filtrată pe norm_set + scale).
 */
final class NormLookup
{
    /** @param list<NormEntry> $entries */
    public function find(array $entries, float $rawScore): ?NormEntry
    {
        foreach ($entries as $entry) {
            if ($entry->containsRawScore($rawScore)) {
                return $entry;
            }
        }

        return null;
    }
}
