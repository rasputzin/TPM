<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Contracts;

use TpmPsych\Domain\Scoring\NormEntry;
use TpmPsych\Domain\Scoring\NormSet;

interface NormRepositoryInterface
{
    public function findSet(int $id): ?NormSet;

    public function findActiveSetForInstrumentVersion(int $instrumentVersionId): ?NormSet;

    /** @return list<NormSet> */
    public function listSetsByInstrumentVersion(int $instrumentVersionId): array;

    public function insertSet(NormSet $set): NormSet;

    public function updateSet(NormSet $set): void;

    public function findEntry(int $id): ?NormEntry;

    /** @return list<NormEntry> */
    public function listEntries(int $normSetId, ?int $scaleId = null): array;

    public function insertEntry(NormEntry $entry): NormEntry;

    public function updateEntry(NormEntry $entry): void;

    public function deleteEntry(int $id): void;
}
