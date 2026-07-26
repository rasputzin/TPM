<?php

declare(strict_types=1);

namespace TpmPsych\Domain\Scoring;

final class NormSet
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $instrumentVersionId,
        public readonly string $label,
        /** @var array<string, mixed>|null */
        public readonly ?array $applicableCriteria,
        public readonly string $versionLabel,
        public readonly string $status,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            instrumentVersionId: (int) $row['instrument_version_id'],
            label: (string) $row['label'],
            applicableCriteria: isset($row['applicable_criteria_json']) && $row['applicable_criteria_json'] !== null
                ? json_decode((string) $row['applicable_criteria_json'], true)
                : null,
            versionLabel: (string) $row['version_label'],
            status: (string) $row['status'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'instrument_version_id' => $this->instrumentVersionId,
            'label' => $this->label,
            'applicable_criteria' => $this->applicableCriteria,
            'version_label' => $this->versionLabel,
            'status' => $this->status,
        ];
    }
}
