<?php

declare(strict_types=1);

namespace TpmPsych\Services;

use TpmPsych\Domain\Report\ReportTemplate;
use TpmPsych\Repositories\Contracts\NormRepositoryInterface;
use TpmPsych\Repositories\Contracts\ReportTemplateRepositoryInterface;
use TpmPsych\Repositories\Contracts\ScaleRepositoryInterface;
use TpmPsych\Support\DomainException;
use TpmPsych\Validation\ReportTemplateValidator;
use TpmPsych\Validation\ValidationException;

final class ReportTemplateService
{
    public function __construct(
        private readonly ReportTemplateRepositoryInterface $templates,
        private readonly NormRepositoryInterface $norms,
        private readonly ScaleRepositoryInterface $scales,
        private readonly ReportTemplateValidator $validator,
        private readonly AuditServiceInterface $audit,
    ) {
    }

    /** @return list<ReportTemplate> */
    public function listByOrganization(int $organizationId): array
    {
        return $this->templates->listByOrganization($organizationId);
    }

    /**
     * @param array<string, mixed> $layoutDefinition
     */
    public function create(
        int $organizationId,
        ?int $instrumentId,
        string $versionLabel,
        array $layoutDefinition,
        ?int $referenceInstrumentVersionId,
        int $actorUserId
    ): ReportTemplate {
        $this->validator->validate($layoutDefinition, $this->knownInterpretationCodes($referenceInstrumentVersionId));

        $template = $this->templates->insert(new ReportTemplate(
            id: null,
            organizationId: $organizationId,
            instrumentId: $instrumentId,
            versionLabel: $versionLabel,
            layoutDefinition: $layoutDefinition,
            status: 'draft',
        ), $actorUserId);

        $this->audit->log('report_template_created', 'report_template', $template->id, $actorUserId, [
            'instrument_id' => $instrumentId,
        ], $organizationId);

        return $template;
    }

    public function publish(int $templateId, int $actorUserId): ReportTemplate
    {
        $template = $this->templates->find($templateId);
        if ($template === null) {
            throw new ValidationException(['Șablonul #' . $templateId . ' nu există.']);
        }

        if ($template->status !== 'draft') {
            throw new DomainException('Șablonul "' . $template->versionLabel . '" nu mai e draft — creează unul nou pentru modificări.');
        }

        $previousActive = $this->templates->findActiveForInstrument($template->organizationId, $template->instrumentId);
        if ($previousActive !== null && $previousActive->id !== $template->id) {
            $this->templates->update(new ReportTemplate(
                id: $previousActive->id,
                organizationId: $previousActive->organizationId,
                instrumentId: $previousActive->instrumentId,
                versionLabel: $previousActive->versionLabel,
                layoutDefinition: $previousActive->layoutDefinition,
                status: 'deprecated',
            ), $actorUserId);
        }

        $activated = new ReportTemplate(
            id: $template->id,
            organizationId: $template->organizationId,
            instrumentId: $template->instrumentId,
            versionLabel: $template->versionLabel,
            layoutDefinition: $template->layoutDefinition,
            status: 'active',
        );
        $this->templates->update($activated, $actorUserId);

        $this->audit->log('report_template_published', 'report_template', $template->id, $actorUserId, [], $template->organizationId);

        return $activated;
    }

    /** @return list<string> */
    private function knownInterpretationCodes(?int $instrumentVersionId): array
    {
        if ($instrumentVersionId === null) {
            return [];
        }

        $codes = [];
        foreach ($this->scales->listByInstrumentVersion($instrumentVersionId) as $scale) {
            $activeSet = $this->norms->findActiveSetForInstrumentVersion($instrumentVersionId);
            if ($activeSet === null) {
                continue;
            }
            foreach ($this->norms->listEntries($activeSet->id, $scale->id) as $entry) {
                if ($entry->interpretationRangeCode !== null) {
                    $codes[] = $entry->interpretationRangeCode;
                }
            }
        }

        return array_values(array_unique($codes));
    }
}
