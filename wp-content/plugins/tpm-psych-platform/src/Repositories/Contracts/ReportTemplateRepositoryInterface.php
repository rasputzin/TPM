<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Contracts;

use TpmPsych\Domain\Report\ReportTemplate;

interface ReportTemplateRepositoryInterface
{
    public function find(int $id): ?ReportTemplate;

    public function findActiveForInstrument(int $organizationId, ?int $instrumentId): ?ReportTemplate;

    /** @return list<ReportTemplate> */
    public function listByOrganization(int $organizationId): array;

    public function insert(ReportTemplate $template, int $actorUserId): ReportTemplate;

    public function update(ReportTemplate $template, int $actorUserId): void;
}
