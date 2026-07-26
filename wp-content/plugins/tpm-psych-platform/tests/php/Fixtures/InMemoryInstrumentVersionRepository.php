<?php

declare(strict_types=1);

namespace TpmPsych\Tests\Fixtures;

use TpmPsych\Domain\Instrument\InstrumentVersion;
use TpmPsych\Repositories\Contracts\InstrumentVersionRepositoryInterface;

final class InMemoryInstrumentVersionRepository implements InstrumentVersionRepositoryInterface
{
    /** @var array<int, InstrumentVersion> */
    private array $rows = [];

    private int $nextId = 1;

    public function find(int $id): ?InstrumentVersion
    {
        return $this->rows[$id] ?? null;
    }

    public function findByInstrumentAndLabel(int $instrumentId, string $versionLabel): ?InstrumentVersion
    {
        foreach ($this->rows as $row) {
            if ($row->instrumentId === $instrumentId && $row->versionLabel === $versionLabel) {
                return $row;
            }
        }

        return null;
    }

    public function findActiveForInstrument(int $instrumentId): ?InstrumentVersion
    {
        foreach ($this->rows as $row) {
            if ($row->instrumentId === $instrumentId && $row->status === InstrumentVersion::STATUS_ACTIVE) {
                return $row;
            }
        }

        return null;
    }

    public function listByInstrument(int $instrumentId): array
    {
        return array_values(array_filter($this->rows, static fn (InstrumentVersion $row) => $row->instrumentId === $instrumentId));
    }

    public function insert(InstrumentVersion $version, int $actorUserId): InstrumentVersion
    {
        $id = $this->nextId++;

        $created = new InstrumentVersion(
            id: $id,
            instrumentId: $version->instrumentId,
            versionLabel: $version->versionLabel,
            language: $version->language,
            estimatedDurationMinutes: $version->estimatedDurationMinutes,
            instructionsText: $version->instructionsText,
            missingResponsePolicy: $version->missingResponsePolicy,
            status: $version->status,
        );

        $this->rows[$id] = $created;

        return $created;
    }

    public function update(InstrumentVersion $version, int $actorUserId): void
    {
        $this->rows[$version->id] = $version;
    }
}
