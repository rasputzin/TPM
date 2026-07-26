<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Contracts;

use TpmPsych\Domain\Instrument\InstrumentVersion;

interface InstrumentVersionRepositoryInterface
{
    public function find(int $id): ?InstrumentVersion;

    public function findByInstrumentAndLabel(int $instrumentId, string $versionLabel): ?InstrumentVersion;

    public function findActiveForInstrument(int $instrumentId): ?InstrumentVersion;

    /** @return list<InstrumentVersion> */
    public function listByInstrument(int $instrumentId): array;

    public function insert(InstrumentVersion $version, int $actorUserId): InstrumentVersion;

    public function update(InstrumentVersion $version, int $actorUserId): void;
}
