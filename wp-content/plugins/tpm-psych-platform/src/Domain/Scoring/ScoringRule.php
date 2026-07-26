<?php

declare(strict_types=1);

namespace TpmPsych\Domain\Scoring;

final class ScoringRule
{
    public const TYPE_SUM = 'sum';
    public const TYPE_WEIGHTED_SUM = 'weighted_sum';
    public const TYPE_FORMULA = 'formula';
    public const TYPE_CONDITIONAL = 'conditional';
    public const TYPE_LOOKUP = 'lookup';

    /** @var list<string> */
    public const VALID_TYPES = [
        self::TYPE_SUM,
        self::TYPE_WEIGHTED_SUM,
        self::TYPE_FORMULA,
        self::TYPE_CONDITIONAL,
        self::TYPE_LOOKUP,
    ];

    public function __construct(
        public readonly ?int $id,
        public readonly int $scaleId,
        public readonly string $ruleType,
        /** @var array<string, mixed> */
        public readonly array $formulaDefinition,
        public readonly string $engineVersion,
        public readonly string $status,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            scaleId: (int) $row['scale_id'],
            ruleType: (string) $row['rule_type'],
            formulaDefinition: json_decode((string) $row['formula_definition_json'], true) ?? [],
            engineVersion: (string) $row['engine_version'],
            status: (string) $row['status'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'scale_id' => $this->scaleId,
            'rule_type' => $this->ruleType,
            'formula_definition' => $this->formulaDefinition,
            'engine_version' => $this->engineVersion,
            'status' => $this->status,
        ];
    }
}
