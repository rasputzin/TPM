<?php

declare(strict_types=1);

namespace TpmPsych\Services\Report;

use TpmPsych\Domain\Instrument\Scale;
use TpmPsych\Domain\Report\ReportTemplate;
use TpmPsych\Services\ScoringEngine\NormLookup;
use TpmPsych\Services\ScoringEngine\ScoringResult;

/**
 * Leagă scorul calculat al unei scale de textul autorizat corespunzător din
 * șablonul de raport, prin `interpretation_range_code` (rezolvat din
 * etalonul activ). Rezultatul e conținut DRAFT — vezi ScaleReportSection.
 */
final class ReportContentResolver
{
    public function __construct(
        private readonly NormLookup $normLookup = new NormLookup(),
    ) {
    }

    /**
     * @param array<string, Scale> $scalesByCode
     * @param array<string, ScoringResult> $scoringResultsByScaleCode
     * @param array<string, list<\TpmPsych\Domain\Scoring\NormEntry>> $normEntriesByScaleCode
     * @return list<ScaleReportSection>
     */
    public function resolve(
        array $scalesByCode,
        array $scoringResultsByScaleCode,
        array $normEntriesByScaleCode,
        ReportTemplate $template
    ): array {
        $textsByCode = [];
        foreach ($template->interpretationTexts() as $text) {
            $textsByCode[$text['interpretation_range_code']] = $text;
        }

        $sections = [];

        foreach ($scalesByCode as $code => $scale) {
            $scoringResult = $scoringResultsByScaleCode[$code] ?? null;
            $normEntries = $normEntriesByScaleCode[$code] ?? [];

            $sections[] = $this->resolveForScale($scale, $scoringResult, $normEntries, $textsByCode);
        }

        return $sections;
    }

    /** @param list<\TpmPsych\Domain\Scoring\NormEntry> $normEntries @param array<string, array{interpretation_range_code: string, title?: string, body: string}> $textsByCode */
    private function resolveForScale(Scale $scale, ?ScoringResult $scoringResult, array $normEntries, array $textsByCode): ScaleReportSection
    {
        if ($scoringResult === null) {
            return new ScaleReportSection(
                $scale->code,
                $scale->name,
                null,
                null,
                null,
                null,
                null,
                null,
                ['Scala nu a fost cotată (nicio regulă de cotare aplicată).']
            );
        }

        if ($scoringResult->rawScore === null) {
            return new ScaleReportSection(
                $scale->code,
                $scale->name,
                null,
                null,
                null,
                null,
                null,
                null,
                array_merge(
                    ['Protocol neinterpretabil pentru această scală: răspunsuri lipsă la itemii ' . implode(', ', $scoringResult->missingItemCodes) . '.'],
                    $scoringResult->warnings
                )
            );
        }

        $normEntry = $this->normLookup->find($normEntries, $scoringResult->rawScore);

        if ($normEntry === null) {
            return new ScaleReportSection(
                $scale->code,
                $scale->name,
                $scoringResult->rawScore,
                null,
                null,
                null,
                null,
                null,
                array_merge(
                    ['Scorul brut nu se încadrează în niciun interval al etalonului activ pentru această scală.'],
                    $scoringResult->warnings
                )
            );
        }

        $text = $textsByCode[$normEntry->interpretationRangeCode] ?? null;

        return new ScaleReportSection(
            $scale->code,
            $scale->name,
            $scoringResult->rawScore,
            $normEntry->standardScoreType,
            $normEntry->standardScoreValue,
            $normEntry->interpretationRangeCode,
            $text['title'] ?? null,
            $text['body'] ?? null,
            $text === null
                ? array_merge(['Nu există text autorizat în șablon pentru codul "' . $normEntry->interpretationRangeCode . '".'], $scoringResult->warnings)
                : $scoringResult->warnings
        );
    }
}
