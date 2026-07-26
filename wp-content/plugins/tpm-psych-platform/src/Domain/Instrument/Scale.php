<?php

declare(strict_types=1);

namespace TpmPsych\Domain\Instrument;

final class Scale
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $instrumentVersionId,
        public readonly ?int $parentScaleId,
        public readonly string $code,
        public readonly string $name,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            instrumentVersionId: (int) $row['instrument_version_id'],
            parentScaleId: isset($row['parent_scale_id']) ? (int) $row['parent_scale_id'] : null,
            code: (string) $row['code'],
            name: (string) $row['name'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'instrument_version_id' => $this->instrumentVersionId,
            'parent_scale_id' => $this->parentScaleId,
            'code' => $this->code,
            'name' => $this->name,
        ];
    }
}
