<?php

declare(strict_types=1);

namespace TpmPsych\Services;

use TpmPsych\Domain\Instrument\Instrument;
use TpmPsych\Repositories\Contracts\InstrumentRepositoryInterface;
use TpmPsych\Validation\ValidationException;

final class InstrumentService
{
    public function __construct(
        private readonly InstrumentRepositoryInterface $instruments,
        private readonly AuditServiceInterface $audit,
    ) {
    }

    public function find(int $id): ?Instrument
    {
        return $this->instruments->find($id);
    }

    /** @return list<Instrument> */
    public function list(?string $status = null): array
    {
        return $this->instruments->list($status);
    }

    public function create(
        ?int $organizationId,
        string $code,
        string $name,
        string $rightsHolder,
        ?string $licenseReference,
        ?string $targetPopulation,
        int $actorUserId
    ): Instrument {
        $code = trim($code);

        if ($code === '' || $name === '' || $rightsHolder === '') {
            throw new ValidationException(['code, name și rights_holder sunt obligatorii.']);
        }

        if ($this->instruments->findByCode($code) !== null) {
            throw new ValidationException(['Există deja un instrument cu codul "' . $code . '".']);
        }

        $instrument = new Instrument(
            id: null,
            organizationId: $organizationId,
            code: $code,
            name: $name,
            rightsHolder: $rightsHolder,
            licenseReference: $licenseReference,
            targetPopulation: $targetPopulation,
            status: 'draft',
        );

        $created = $this->instruments->insert($instrument, $actorUserId);

        $this->audit->log('instrument_created', 'instrument', $created->id, $actorUserId, ['code' => $created->code]);

        return $created;
    }

    public function update(
        int $id,
        string $name,
        string $rightsHolder,
        ?string $licenseReference,
        ?string $targetPopulation,
        int $actorUserId
    ): Instrument {
        $existing = $this->instruments->find($id);

        if ($existing === null) {
            throw new ValidationException(['Instrumentul #' . $id . ' nu există.']);
        }

        $updated = new Instrument(
            id: $existing->id,
            organizationId: $existing->organizationId,
            code: $existing->code,
            name: $name,
            rightsHolder: $rightsHolder,
            licenseReference: $licenseReference,
            targetPopulation: $targetPopulation,
            status: $existing->status,
        );

        $this->instruments->update($updated, $actorUserId);
        $this->audit->log('instrument_updated', 'instrument', $id, $actorUserId, []);

        return $updated;
    }

    public function archive(int $id, int $actorUserId): void
    {
        $existing = $this->instruments->find($id);

        if ($existing === null) {
            throw new ValidationException(['Instrumentul #' . $id . ' nu există.']);
        }

        $archived = new Instrument(
            id: $existing->id,
            organizationId: $existing->organizationId,
            code: $existing->code,
            name: $existing->name,
            rightsHolder: $existing->rightsHolder,
            licenseReference: $existing->licenseReference,
            targetPopulation: $existing->targetPopulation,
            status: 'archived',
        );

        $this->instruments->update($archived, $actorUserId);
        $this->audit->log('instrument_archived', 'instrument', $id, $actorUserId, []);
    }
}
