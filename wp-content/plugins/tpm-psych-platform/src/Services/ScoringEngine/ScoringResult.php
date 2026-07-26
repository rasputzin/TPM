<?php

declare(strict_types=1);

namespace TpmPsych\Services\ScoringEngine;

/**
 * Rezultatul cotării unei singure scale. `rawScore` e null când protocolul
 * e neinterpretabil (variabile lipsă pe care regula nu le poate ignora) —
 * apelantul decide cum tratează asta (blochează raportul, marchează
 * avertisment etc.), motorul doar raportează faptele.
 */
final class ScoringResult
{
    /**
     * @param list<string> $missingItemCodes coduri de item lipsă din răspunsuri, referite de regulă
     * @param list<string> $warnings avertismente non-blocante (ex. itemi omiși dintr-o sumă)
     */
    public function __construct(
        public readonly ?float $rawScore,
        public readonly array $missingItemCodes,
        public readonly array $warnings,
    ) {
    }

    public function isComplete(): bool
    {
        return $this->missingItemCodes === [];
    }

    public static function computed(float $rawScore, array $warnings = []): self
    {
        return new self($rawScore, [], $warnings);
    }

    /** @param list<string> $missingItemCodes */
    public static function incomplete(array $missingItemCodes, array $warnings = []): self
    {
        return new self(null, $missingItemCodes, $warnings);
    }
}
