<?php

declare(strict_types=1);

namespace TpmPsych\Services\Report;

/**
 * Conținutul (draft, needitat) generat automat pentru o singură scală în
 * cadrul unui raport: scor + interpretare psihometrică + text autorizat.
 * Observațiile, concluzia și recomandările psihologului NU fac parte din
 * acest obiect — se adaugă separat, la validarea raportului (vezi
 * docs/psychology-platform/02-arhitectura-recomandata.md, principiul de
 * separare scor/interpretare/validare profesională).
 */
final class ScaleReportSection
{
    /** @param list<string> $warnings */
    public function __construct(
        public readonly string $scaleCode,
        public readonly string $scaleName,
        public readonly ?float $rawScore,
        public readonly ?string $standardScoreType,
        public readonly ?float $standardScoreValue,
        public readonly ?string $interpretationRangeCode,
        public readonly ?string $interpretationTitle,
        public readonly ?string $interpretationBody,
        public readonly array $warnings,
    ) {
    }

    public function isInterpretable(): bool
    {
        return $this->rawScore !== null && $this->interpretationRangeCode !== null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'scale_code' => $this->scaleCode,
            'scale_name' => $this->scaleName,
            'raw_score' => $this->rawScore,
            'standard_score_type' => $this->standardScoreType,
            'standard_score_value' => $this->standardScoreValue,
            'interpretation_range_code' => $this->interpretationRangeCode,
            'interpretation_title' => $this->interpretationTitle,
            'interpretation_body' => $this->interpretationBody,
            'warnings' => $this->warnings,
        ];
    }
}
