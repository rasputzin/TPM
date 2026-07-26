<?php

declare(strict_types=1);

namespace TpmPsych\Validation;

use TpmPsych\Domain\Scoring\NormEntry;

/**
 * Verifică faptul că intrările unui etalon (norm_set + scale) nu se
 * suprapun și nu lasă găuri neintenționate — o gaură silențioasă ar
 * însemna scoruri brute pentru care nu există conversie standardizată,
 * detectabil abia la cotarea unui răspuns real dacă nu e prins aici.
 */
final class NormRangeValidator
{
    /**
     * @param list<NormEntry> $existingEntriesSameScale intrări deja salvate pentru același norm_set+scale
     */
    public function assertNoOverlap(NormEntry $candidate, array $existingEntriesSameScale): void
    {
        if ($candidate->rawScoreMin > $candidate->rawScoreMax) {
            throw new ValidationException(['raw_score_min nu poate fi mai mare decât raw_score_max.']);
        }

        foreach ($existingEntriesSameScale as $existing) {
            if ($existing->id === $candidate->id) {
                continue;
            }

            $overlaps = $candidate->rawScoreMin <= $existing->rawScoreMax
                && $candidate->rawScoreMax >= $existing->rawScoreMin;

            if ($overlaps) {
                throw new ValidationException([
                    sprintf(
                        'Intervalul [%s, %s] se suprapune cu un interval existent [%s, %s] pentru aceeași scală/etalon.',
                        $candidate->rawScoreMin,
                        $candidate->rawScoreMax,
                        $existing->rawScoreMin,
                        $existing->rawScoreMax
                    ),
                ]);
            }
        }
    }

    /**
     * Semnalează găuri (informativ — nu blochează salvarea unei singure
     * intrări, dar poate fi apelat la publicarea unui norm_set complet).
     *
     * @param list<NormEntry> $entriesSameScale
     * @return list<string>
     */
    public function findGaps(array $entriesSameScale): array
    {
        if ($entriesSameScale === []) {
            return ['Etalonul nu are nicio intrare definită pentru această scală.'];
        }

        $sorted = $entriesSameScale;
        usort($sorted, static fn (NormEntry $a, NormEntry $b): int => $a->rawScoreMin <=> $b->rawScoreMin);

        $gaps = [];
        for ($i = 1; $i < count($sorted); $i++) {
            $previous = $sorted[$i - 1];
            $current = $sorted[$i];

            if ($current->rawScoreMin > $previous->rawScoreMax + 0.0001) {
                $gaps[] = sprintf('Gol neacoperit între %s și %s.', $previous->rawScoreMax, $current->rawScoreMin);
            }
        }

        return $gaps;
    }
}
