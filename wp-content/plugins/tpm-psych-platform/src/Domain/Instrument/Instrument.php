<?php

declare(strict_types=1);

namespace TpmPsych\Domain\Instrument;

final class Instrument
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $organizationId,
        public readonly string $code,
        public readonly string $name,
        public readonly string $rightsHolder,
        public readonly ?string $licenseReference,
        public readonly ?string $targetPopulation,
        public readonly string $status,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            organizationId: isset($row['organization_id']) ? (int) $row['organization_id'] : null,
            code: (string) $row['code'],
            name: (string) $row['name'],
            rightsHolder: (string) $row['rights_holder'],
            licenseReference: $row['license_reference'] ?? null,
            targetPopulation: $row['target_population'] ?? null,
            status: (string) $row['status'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'code' => $this->code,
            'name' => $this->name,
            'rights_holder' => $this->rightsHolder,
            'license_reference' => $this->licenseReference,
            'target_population' => $this->targetPopulation,
            'status' => $this->status,
        ];
    }
}
