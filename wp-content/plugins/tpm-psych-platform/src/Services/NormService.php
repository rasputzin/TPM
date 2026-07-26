<?php

declare(strict_types=1);

namespace TpmPsych\Services;

use TpmPsych\Domain\Scoring\NormEntry;
use TpmPsych\Domain\Scoring\NormSet;
use TpmPsych\Repositories\Contracts\NormRepositoryInterface;
use TpmPsych\Repositories\Contracts\ScaleRepositoryInterface;
use TpmPsych\Support\DomainException;
use TpmPsych\Validation\NormRangeValidator;
use TpmPsych\Validation\ValidationException;

/**
 * Etaloanele (norm sets) au propria versionare, independentă de versiunea
 * instrumentului: un norm_set poate fi adăugat oricând (chiar la un
 * instrument deja activ), dar odată publicat (status=active) devine
 * imutabil — la fel ca instrument_versions, orice corecție ulterioară
 * creează un norm_set nou, nu editează unul folosit deja la cotare.
 */
final class NormService
{
    public function __construct(
        private readonly NormRepositoryInterface $norms,
        private readonly ScaleRepositoryInterface $scales,
        private readonly NormRangeValidator $rangeValidator,
        private readonly AuditServiceInterface $audit,
    ) {
    }

    /** @return list<NormSet> */
    public function listSets(int $instrumentVersionId): array
    {
        return $this->norms->listSetsByInstrumentVersion($instrumentVersionId);
    }

    /** @param array<string, mixed>|null $applicableCriteria */
    public function createSet(
        int $instrumentVersionId,
        string $label,
        string $versionLabel,
        ?array $applicableCriteria,
        int $actorUserId
    ): NormSet {
        if (trim($label) === '' || trim($versionLabel) === '') {
            throw new ValidationException(['label și version_label sunt obligatorii pentru un etalon.']);
        }

        $set = $this->norms->insertSet(new NormSet(
            id: null,
            instrumentVersionId: $instrumentVersionId,
            label: $label,
            applicableCriteria: $applicableCriteria,
            versionLabel: $versionLabel,
            status: 'draft',
        ));

        $this->audit->log('norm_set_created', 'norm_set', $set->id, $actorUserId, ['instrument_version_id' => $instrumentVersionId]);

        return $set;
    }

    /** @return list<NormEntry> */
    public function listEntries(int $normSetId, ?int $scaleId = null): array
    {
        return $this->norms->listEntries($normSetId, $scaleId);
    }

    public function addEntry(
        int $normSetId,
        int $scaleId,
        float $rawScoreMin,
        float $rawScoreMax,
        string $standardScoreType,
        float $standardScoreValue,
        ?string $interpretationRangeCode,
        int $actorUserId
    ): NormEntry {
        $set = $this->requireDraftSet($normSetId);

        $scale = $this->scales->find($scaleId);
        if ($scale === null || $scale->instrumentVersionId !== $set->instrumentVersionId) {
            throw new ValidationException(['Scala #' . $scaleId . ' nu aparține versiunii instrumentului pentru acest etalon.']);
        }

        if (! in_array($standardScoreType, NormEntry::VALID_TYPES, true)) {
            throw new ValidationException(['Tip de scor standardizat necunoscut: "' . $standardScoreType . '".']);
        }

        $candidate = new NormEntry(
            id: null,
            normSetId: $normSetId,
            scaleId: $scaleId,
            rawScoreMin: $rawScoreMin,
            rawScoreMax: $rawScoreMax,
            standardScoreType: $standardScoreType,
            standardScoreValue: $standardScoreValue,
            interpretationRangeCode: $interpretationRangeCode,
        );

        $this->rangeValidator->assertNoOverlap($candidate, $this->norms->listEntries($normSetId, $scaleId));

        $entry = $this->norms->insertEntry($candidate);
        $this->audit->log('norm_entry_created', 'norm_entry', $entry->id, $actorUserId, ['norm_set_id' => $normSetId, 'scale_id' => $scaleId]);

        return $entry;
    }

    public function publishSet(int $normSetId, int $actorUserId): NormSet
    {
        $set = $this->requireDraftSet($normSetId);

        foreach ($this->scales->listByInstrumentVersion($set->instrumentVersionId) as $scale) {
            $gaps = (new NormRangeValidator())->findGaps($this->norms->listEntries($normSetId, $scale->id));
            if ($gaps !== []) {
                throw new ValidationException(array_map(
                    static fn (string $gap) => 'Scala "' . $scale->code . '": ' . $gap,
                    $gaps
                ));
            }
        }

        $previousActive = $this->norms->findActiveSetForInstrumentVersion($set->instrumentVersionId);
        if ($previousActive !== null) {
            $this->norms->updateSet(new NormSet(
                id: $previousActive->id,
                instrumentVersionId: $previousActive->instrumentVersionId,
                label: $previousActive->label,
                applicableCriteria: $previousActive->applicableCriteria,
                versionLabel: $previousActive->versionLabel,
                status: 'deprecated',
            ));
        }

        $activated = new NormSet(
            id: $set->id,
            instrumentVersionId: $set->instrumentVersionId,
            label: $set->label,
            applicableCriteria: $set->applicableCriteria,
            versionLabel: $set->versionLabel,
            status: 'active',
        );
        $this->norms->updateSet($activated);

        $this->audit->log('norm_set_published', 'norm_set', $set->id, $actorUserId, [
            'previous_active_norm_set_id' => $previousActive?->id,
        ]);

        return $activated;
    }

    private function requireDraftSet(int $normSetId): NormSet
    {
        $set = $this->norms->findSet($normSetId);
        if ($set === null) {
            throw new ValidationException(['Etalonul #' . $normSetId . ' nu există.']);
        }

        if ($set->status !== 'draft') {
            throw new DomainException('Etalonul "' . $set->label . '" (status: ' . $set->status . ') e imutabil. Creează un etalon nou pentru corecții.');
        }

        return $set;
    }
}
