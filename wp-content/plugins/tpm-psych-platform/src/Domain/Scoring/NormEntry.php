<?php

declare(strict_types=1);

namespace TpmPsych\Domain\Scoring;

final class NormEntry
{
    public const TYPE_PERCENTILE = 'percentile';
    public const TYPE_T_SCORE = 't_score';
    public const TYPE_Z_SCORE = 'z_score';
    public const TYPE_STANINE = 'stanine';
    public const TYPE_CUSTOM = 'custom';

    /** @var list<string> */
    public const VALID_TYPES = [
        self::TYPE_PERCENTILE,
        self::TYPE_T_SCORE,
        self::TYPE_Z_SCORE,
        self::TYPE_STANINE,
        self::TYPE_CUSTOM,
    ];

    public function __construct(
        public readonly ?int $id,
        public readonly int $normSetId,
        public readonly int $scaleId,
        public readonly float $rawScoreMin,
        public readonly float $rawScoreMax,
        public readonly string $standardScoreType,
        public readonly float $standardScoreValue,
        public readonly ?string $interpretationRangeCode,
    ) {
    }

    public function containsRawScore(float $rawScore): bool
    {
        return $rawScore >= $this->rawScoreMin && $rawScore <= $this->rawScoreMax;
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            normSetId: (int) $row['norm_set_id'],
            scaleId: (int) $row['scale_id'],
            rawScoreMin: (float) $row['raw_score_min'],
            rawScoreMax: (float) $row['raw_score_max'],
            standardScoreType: (string) $row['standard_score_type'],
            standardScoreValue: (float) $row['standard_score_value'],
            interpretationRangeCode: $row['interpretation_range_code'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'norm_set_id' => $this->normSetId,
            'scale_id' => $this->scaleId,
            'raw_score_min' => $this->rawScoreMin,
            'raw_score_max' => $this->rawScoreMax,
            'standard_score_type' => $this->standardScoreType,
            'standard_score_value' => $this->standardScoreValue,
            'interpretation_range_code' => $this->interpretationRangeCode,
        ];
    }
}
