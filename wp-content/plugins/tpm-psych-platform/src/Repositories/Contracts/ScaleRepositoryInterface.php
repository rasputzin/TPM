<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Contracts;

use TpmPsych\Domain\Instrument\Scale;

interface ScaleRepositoryInterface
{
    public function find(int $id): ?Scale;

    /** @return list<Scale> */
    public function listByInstrumentVersion(int $instrumentVersionId): array;

    public function insert(Scale $scale): Scale;

    public function update(Scale $scale): void;

    public function delete(int $id): void;
}
