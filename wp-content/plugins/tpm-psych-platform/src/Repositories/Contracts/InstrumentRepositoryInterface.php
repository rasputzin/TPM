<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Contracts;

use TpmPsych\Domain\Instrument\Instrument;

interface InstrumentRepositoryInterface
{
    public function find(int $id): ?Instrument;

    public function findByCode(string $code): ?Instrument;

    /** @return list<Instrument> */
    public function list(?string $status = null): array;

    public function insert(Instrument $instrument, int $actorUserId): Instrument;

    public function update(Instrument $instrument, int $actorUserId): void;
}
