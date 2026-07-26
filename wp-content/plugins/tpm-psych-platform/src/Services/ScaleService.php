<?php

declare(strict_types=1);

namespace TpmPsych\Services;

use TpmPsych\Domain\Instrument\Scale;
use TpmPsych\Repositories\Contracts\ScaleRepositoryInterface;
use TpmPsych\Validation\ValidationException;

final class ScaleService
{
    public function __construct(
        private readonly ScaleRepositoryInterface $scales,
        private readonly InstrumentVersionService $versions,
        private readonly AuditServiceInterface $audit,
    ) {
    }

    /** @return list<Scale> */
    public function listByInstrumentVersion(int $instrumentVersionId): array
    {
        return $this->scales->listByInstrumentVersion($instrumentVersionId);
    }

    public function find(int $id): ?Scale
    {
        return $this->scales->find($id);
    }

    public function add(int $instrumentVersionId, string $code, string $name, ?int $parentScaleId, int $actorUserId): Scale
    {
        $this->versions->requireDraft($instrumentVersionId);

        $code = trim($code);
        if ($code === '' || $name === '') {
            throw new ValidationException(['code și name sunt obligatorii pentru o scală.']);
        }

        foreach ($this->scales->listByInstrumentVersion($instrumentVersionId) as $existing) {
            if ($existing->code === $code) {
                throw new ValidationException(['Există deja o scală cu codul "' . $code . '" în această versiune.']);
            }
        }

        $scale = $this->scales->insert(new Scale(
            id: null,
            instrumentVersionId: $instrumentVersionId,
            parentScaleId: $parentScaleId,
            code: $code,
            name: $name,
        ));

        $this->audit->log('scale_created', 'scale', $scale->id, $actorUserId, ['instrument_version_id' => $instrumentVersionId]);

        return $scale;
    }

    public function update(int $scaleId, string $name, int $actorUserId): Scale
    {
        $scale = $this->scales->find($scaleId);
        if ($scale === null) {
            throw new ValidationException(['Scala #' . $scaleId . ' nu există.']);
        }

        $this->versions->requireDraft($scale->instrumentVersionId);

        $updated = new Scale(
            id: $scale->id,
            instrumentVersionId: $scale->instrumentVersionId,
            parentScaleId: $scale->parentScaleId,
            code: $scale->code,
            name: $name,
        );

        $this->scales->update($updated);
        $this->audit->log('scale_updated', 'scale', $scaleId, $actorUserId, []);

        return $updated;
    }
}
