<?php

declare(strict_types=1);

namespace TpmPsych\Domain\Instrument;

final class InstrumentVersion
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DEPRECATED = 'deprecated';

    public function __construct(
        public readonly ?int $id,
        public readonly int $instrumentId,
        public readonly string $versionLabel,
        public readonly string $language,
        public readonly ?int $estimatedDurationMinutes,
        public readonly ?string $instructionsText,
        public readonly ?array $missingResponsePolicy,
        public readonly string $status,
    ) {
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            instrumentId: (int) $row['instrument_id'],
            versionLabel: (string) $row['version_label'],
            language: (string) $row['language'],
            estimatedDurationMinutes: isset($row['estimated_duration_minutes']) ? (int) $row['estimated_duration_minutes'] : null,
            instructionsText: $row['instructions_text'] ?? null,
            missingResponsePolicy: isset($row['missing_response_policy_json']) && $row['missing_response_policy_json'] !== null
                ? json_decode((string) $row['missing_response_policy_json'], true)
                : null,
            status: (string) $row['status'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'instrument_id' => $this->instrumentId,
            'version_label' => $this->versionLabel,
            'language' => $this->language,
            'estimated_duration_minutes' => $this->estimatedDurationMinutes,
            'instructions_text' => $this->instructionsText,
            'missing_response_policy' => $this->missingResponsePolicy,
            'status' => $this->status,
        ];
    }
}
