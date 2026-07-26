<?php

declare(strict_types=1);

namespace TpmPsych\Services;

use TpmPsych\Domain\Instrument\InstrumentVersion;
use TpmPsych\Domain\Instrument\Item;
use TpmPsych\Domain\Instrument\ResponseOption;
use TpmPsych\Domain\Instrument\Scale;
use TpmPsych\Domain\Instrument\Section;
use TpmPsych\Domain\Scoring\ScoringRule;
use TpmPsych\Repositories\Contracts\InstrumentStructureRepositoryInterface;
use TpmPsych\Repositories\Contracts\InstrumentVersionRepositoryInterface;
use TpmPsych\Repositories\Contracts\ScaleRepositoryInterface;
use TpmPsych\Repositories\Contracts\ScoringRuleRepositoryInterface;
use TpmPsych\Support\DomainException;
use TpmPsych\Validation\ValidationException;

/**
 * Orchestrează versionarea imutabilă a instrumentelor: orice modificare de
 * conținut (itemi, scale, reguli de cotare) e permisă doar cât timp versiunea
 * e "draft"; publicarea o face imutabilă și retrogradează versiunea activă
 * anterioară la "deprecated" (fără a o șterge — evaluările istorice rămân
 * legate de versiunea exactă folosită, conform cerinței de nemodificare
 * retroactivă a rezultatelor).
 */
final class InstrumentVersionService
{
    public function __construct(
        private readonly InstrumentVersionRepositoryInterface $versions,
        private readonly InstrumentStructureRepositoryInterface $structure,
        private readonly ScaleRepositoryInterface $scales,
        private readonly ScoringRuleRepositoryInterface $scoringRules,
        private readonly AuditServiceInterface $audit,
    ) {
    }

    public function find(int $id): ?InstrumentVersion
    {
        return $this->versions->find($id);
    }

    /** @return list<InstrumentVersion> */
    public function listByInstrument(int $instrumentId): array
    {
        return $this->versions->listByInstrument($instrumentId);
    }

    public function createEmptyDraft(
        int $instrumentId,
        string $versionLabel,
        string $language,
        int $actorUserId
    ): InstrumentVersion {
        if ($this->versions->findByInstrumentAndLabel($instrumentId, $versionLabel) !== null) {
            throw new ValidationException(['Există deja o versiune "' . $versionLabel . '" pentru acest instrument.']);
        }

        $draft = new InstrumentVersion(
            id: null,
            instrumentId: $instrumentId,
            versionLabel: $versionLabel,
            language: $language,
            estimatedDurationMinutes: null,
            instructionsText: null,
            missingResponsePolicy: null,
            status: InstrumentVersion::STATUS_DRAFT,
        );

        $created = $this->versions->insert($draft, $actorUserId);
        $this->audit->log('instrument_version_created', 'instrument_version', $created->id, $actorUserId, [
            'instrument_id' => $instrumentId,
            'version_label' => $versionLabel,
        ]);

        return $created;
    }

    /**
     * Creează o nouă versiune draft pornind de la structura completă a unei
     * versiuni existente (secțiuni, itemi, opțiuni, scale, reguli de cotare
     * — regulile clonate pornesc tot de la "draft", trebuie republicate).
     */
    public function cloneAsDraft(int $sourceVersionId, string $newVersionLabel, int $actorUserId): InstrumentVersion
    {
        $source = $this->versions->find($sourceVersionId);

        if ($source === null) {
            throw new ValidationException(['Versiunea sursă #' . $sourceVersionId . ' nu există.']);
        }

        $draft = $this->createEmptyDraft($source->instrumentId, $newVersionLabel, $source->language, $actorUserId);

        $sectionIdMap = [];
        foreach ($this->structure->listSections($source->id) as $section) {
            $clone = $this->structure->insertSection(new Section(
                id: null,
                instrumentVersionId: $draft->id,
                parentSectionId: $section->parentSectionId !== null ? ($sectionIdMap[$section->parentSectionId] ?? null) : null,
                title: $section->title,
                orderIndex: $section->orderIndex,
                branchingRule: $section->branchingRule,
            ));
            $sectionIdMap[$section->id] = $clone->id;
        }

        foreach ($this->structure->listItems($source->id) as $item) {
            $clonedItem = $this->structure->insertItem(new Item(
                id: null,
                instrumentVersionId: $draft->id,
                sectionId: $item->sectionId !== null ? ($sectionIdMap[$item->sectionId] ?? null) : null,
                code: $item->code,
                promptText: $item->promptText,
                itemType: $item->itemType,
                isReverseScored: $item->isReverseScored,
                isRequired: $item->isRequired,
                conditionalRule: $item->conditionalRule,
                orderIndex: $item->orderIndex,
            ));

            foreach ($this->structure->listResponseOptions($item->id) as $option) {
                $this->structure->insertResponseOption(new ResponseOption(
                    id: null,
                    itemId: $clonedItem->id,
                    labelText: $option->labelText,
                    rawValue: $option->rawValue,
                    orderIndex: $option->orderIndex,
                ));
            }
        }

        $scaleIdMap = [];
        $sourceScales = $this->scales->listByInstrumentVersion($source->id);
        foreach ($sourceScales as $scale) {
            $clone = $this->scales->insert(new Scale(
                id: null,
                instrumentVersionId: $draft->id,
                parentScaleId: $scale->parentScaleId !== null ? ($scaleIdMap[$scale->parentScaleId] ?? null) : null,
                code: $scale->code,
                name: $scale->name,
            ));
            $scaleIdMap[$scale->id] = $clone->id;
        }

        foreach ($sourceScales as $scale) {
            $rule = $this->scoringRules->findActiveByScale($scale->id);
            if ($rule === null) {
                continue;
            }

            $this->scoringRules->insert(new ScoringRule(
                id: null,
                scaleId: $scaleIdMap[$scale->id],
                ruleType: $rule->ruleType,
                formulaDefinition: $rule->formulaDefinition,
                engineVersion: $rule->engineVersion,
                status: 'draft',
            ));
        }

        $this->audit->log('instrument_version_cloned', 'instrument_version', $draft->id, $actorUserId, [
            'source_version_id' => $sourceVersionId,
        ]);

        return $draft;
    }

    public function updateDraftMetadata(
        int $versionId,
        string $language,
        ?int $estimatedDurationMinutes,
        ?string $instructionsText,
        ?array $missingResponsePolicy,
        int $actorUserId
    ): InstrumentVersion {
        $version = $this->requireDraft($versionId);

        $updated = new InstrumentVersion(
            id: $version->id,
            instrumentId: $version->instrumentId,
            versionLabel: $version->versionLabel,
            language: $language,
            estimatedDurationMinutes: $estimatedDurationMinutes,
            instructionsText: $instructionsText,
            missingResponsePolicy: $missingResponsePolicy,
            status: $version->status,
        );

        $this->versions->update($updated, $actorUserId);

        return $updated;
    }

    /**
     * Ridică excepție dacă versiunea nu e "draft" — apelat de toate
     * serviciile care modifică structura (secțiuni/itemi/scale/reguli).
     */
    public function requireDraft(int $versionId): InstrumentVersion
    {
        $version = $this->versions->find($versionId);

        if ($version === null) {
            throw new ValidationException(['Versiunea #' . $versionId . ' nu există.']);
        }

        if (! $version->isDraft()) {
            throw new DomainException(
                'Versiunea "' . $version->versionLabel . '" (status: ' . $version->status . ') nu mai poate fi modificată. '
                . 'Creează o versiune nouă (draft) dacă ai nevoie să schimbi itemi, scale, reguli de cotare sau etaloane.'
            );
        }

        return $version;
    }

    public function publish(int $versionId, int $actorUserId): InstrumentVersion
    {
        $version = $this->requireDraft($versionId);
        $this->assertPublishable($version);

        $previousActive = $this->versions->findActiveForInstrument($version->instrumentId);
        if ($previousActive !== null) {
            $deprecated = new InstrumentVersion(
                id: $previousActive->id,
                instrumentId: $previousActive->instrumentId,
                versionLabel: $previousActive->versionLabel,
                language: $previousActive->language,
                estimatedDurationMinutes: $previousActive->estimatedDurationMinutes,
                instructionsText: $previousActive->instructionsText,
                missingResponsePolicy: $previousActive->missingResponsePolicy,
                status: InstrumentVersion::STATUS_DEPRECATED,
            );
            $this->versions->update($deprecated, $actorUserId);
        }

        foreach ($this->scales->listByInstrumentVersion($version->id) as $scale) {
            foreach ($this->scoringRules->listByScale($scale->id) as $rule) {
                if ($rule->status === 'draft') {
                    $this->scoringRules->update(new ScoringRule(
                        id: $rule->id,
                        scaleId: $rule->scaleId,
                        ruleType: $rule->ruleType,
                        formulaDefinition: $rule->formulaDefinition,
                        engineVersion: $rule->engineVersion,
                        status: 'active',
                    ));
                }
            }
        }

        $activated = new InstrumentVersion(
            id: $version->id,
            instrumentId: $version->instrumentId,
            versionLabel: $version->versionLabel,
            language: $version->language,
            estimatedDurationMinutes: $version->estimatedDurationMinutes,
            instructionsText: $version->instructionsText,
            missingResponsePolicy: $version->missingResponsePolicy,
            status: InstrumentVersion::STATUS_ACTIVE,
        );
        $this->versions->update($activated, $actorUserId);

        $this->audit->log('instrument_version_published', 'instrument_version', $version->id, $actorUserId, [
            'previous_active_version_id' => $previousActive?->id,
        ]);

        return $this->versions->find($version->id);
    }

    private function assertPublishable(InstrumentVersion $version): void
    {
        $items = $this->structure->listItems($version->id);
        if ($items === []) {
            throw new ValidationException(['Versiunea nu poate fi publicată fără niciun item.']);
        }

        $scales = $this->scales->listByInstrumentVersion($version->id);
        if ($scales === []) {
            throw new ValidationException(['Versiunea nu poate fi publicată fără nicio scală definită.']);
        }

        foreach ($scales as $scale) {
            $hasRule = false;
            foreach ($this->scoringRules->listByScale($scale->id) as $rule) {
                if (in_array($rule->status, ['draft', 'active'], true)) {
                    $hasRule = true;
                    break;
                }
            }

            if (! $hasRule) {
                throw new ValidationException(['Scala "' . $scale->code . '" nu are nicio regulă de cotare definită.']);
            }
        }
    }
}
