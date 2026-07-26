<?php

declare(strict_types=1);

namespace TpmPsych\Domain\Instrument;

final class Section
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $instrumentVersionId,
        public readonly ?int $parentSectionId,
        public readonly ?string $title,
        public readonly int $orderIndex,
        public readonly ?array $branchingRule,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            instrumentVersionId: (int) $row['instrument_version_id'],
            parentSectionId: isset($row['parent_section_id']) ? (int) $row['parent_section_id'] : null,
            title: $row['title'] ?? null,
            orderIndex: (int) ($row['order_index'] ?? 0),
            branchingRule: isset($row['branching_rule_json']) && $row['branching_rule_json'] !== null
                ? json_decode((string) $row['branching_rule_json'], true)
                : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'instrument_version_id' => $this->instrumentVersionId,
            'parent_section_id' => $this->parentSectionId,
            'title' => $this->title,
            'order_index' => $this->orderIndex,
            'branching_rule' => $this->branchingRule,
        ];
    }
}
