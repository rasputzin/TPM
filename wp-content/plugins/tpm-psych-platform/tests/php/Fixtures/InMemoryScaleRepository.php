<?php

declare(strict_types=1);

namespace TpmPsych\Tests\Fixtures;

use TpmPsych\Domain\Instrument\Scale;
use TpmPsych\Repositories\Contracts\ScaleRepositoryInterface;

final class InMemoryScaleRepository implements ScaleRepositoryInterface
{
    /** @var array<int, Scale> */
    private array $rows = [];

    private int $nextId = 1;

    public function find(int $id): ?Scale
    {
        return $this->rows[$id] ?? null;
    }

    public function listByInstrumentVersion(int $instrumentVersionId): array
    {
        return array_values(array_filter($this->rows, static fn (Scale $s) => $s->instrumentVersionId === $instrumentVersionId));
    }

    public function insert(Scale $scale): Scale
    {
        $id = $this->nextId++;
        $created = new Scale($id, $scale->instrumentVersionId, $scale->parentScaleId, $scale->code, $scale->name);
        $this->rows[$id] = $created;

        return $created;
    }

    public function update(Scale $scale): void
    {
        $this->rows[$scale->id] = $scale;
    }

    public function delete(int $id): void
    {
        unset($this->rows[$id]);
    }
}
