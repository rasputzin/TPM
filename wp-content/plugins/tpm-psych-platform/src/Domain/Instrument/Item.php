<?php

declare(strict_types=1);

namespace TpmPsych\Domain\Instrument;

final class Item
{
    public const TYPE_LIKERT = 'likert';
    public const TYPE_SINGLE_CHOICE = 'single_choice';
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_NUMERIC = 'numeric';
    public const TYPE_FREE_TEXT = 'free_text';
    public const TYPE_MATRIX = 'matrix';

    /** @var list<string> */
    public const VALID_TYPES = [
        self::TYPE_LIKERT,
        self::TYPE_SINGLE_CHOICE,
        self::TYPE_MULTIPLE_CHOICE,
        self::TYPE_BOOLEAN,
        self::TYPE_NUMERIC,
        self::TYPE_FREE_TEXT,
        self::TYPE_MATRIX,
    ];

    public function __construct(
        public readonly ?int $id,
        public readonly int $instrumentVersionId,
        public readonly ?int $sectionId,
        public readonly string $code,
        public readonly string $promptText,
        public readonly string $itemType,
        public readonly bool $isReverseScored,
        public readonly bool $isRequired,
        public readonly ?array $conditionalRule,
        public readonly int $orderIndex,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            instrumentVersionId: (int) $row['instrument_version_id'],
            sectionId: isset($row['section_id']) ? (int) $row['section_id'] : null,
            code: (string) $row['code'],
            promptText: (string) $row['prompt_text'],
            itemType: (string) $row['item_type'],
            isReverseScored: (bool) ($row['is_reverse_scored'] ?? false),
            isRequired: (bool) ($row['is_required'] ?? true),
            conditionalRule: isset($row['conditional_rule_json']) && $row['conditional_rule_json'] !== null
                ? json_decode((string) $row['conditional_rule_json'], true)
                : null,
            orderIndex: (int) ($row['order_index'] ?? 0),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'instrument_version_id' => $this->instrumentVersionId,
            'section_id' => $this->sectionId,
            'code' => $this->code,
            'prompt_text' => $this->promptText,
            'item_type' => $this->itemType,
            'is_reverse_scored' => $this->isReverseScored,
            'is_required' => $this->isRequired,
            'conditional_rule' => $this->conditionalRule,
            'order_index' => $this->orderIndex,
        ];
    }

    /**
     * DTO minimal pentru frontend-ul respondentului: fără is_reverse_scored
     * și fără orice metadate de cotare — vezi threat model, punctul 10.
     *
     * @return array<string, mixed>
     */
    public function toRespondentPayload(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'prompt_text' => $this->promptText,
            'item_type' => $this->itemType,
            'is_required' => $this->isRequired,
            'order_index' => $this->orderIndex,
        ];
    }
}
