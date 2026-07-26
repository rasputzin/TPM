<?php

declare(strict_types=1);

namespace TpmPsych\Domain\Report;

/**
 * `layout_definition_json` conține, printre altele, cheia
 * `interpretation_texts`: o listă de blocuri text autorizate, fiecare legat
 * de un `interpretation_range_code` (definit la nivelul `norm_entries`), plus
 * o listă de secțiuni fixe ale raportului (identitate cabinet, instrumente
 * folosite, tabele, grafice etc.). Structura exactă e validată de
 * `ReportTemplateService`/`ReportTemplateValidator`, nu doar stocată liber.
 */
final class ReportTemplate
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $organizationId,
        public readonly ?int $instrumentId,
        public readonly string $versionLabel,
        /** @var array<string, mixed> */
        public readonly array $layoutDefinition,
        public readonly string $status,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            organizationId: (int) $row['organization_id'],
            instrumentId: isset($row['instrument_id']) ? (int) $row['instrument_id'] : null,
            versionLabel: (string) $row['version_label'],
            layoutDefinition: json_decode((string) $row['layout_definition_json'], true) ?? [],
            status: (string) $row['status'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'instrument_id' => $this->instrumentId,
            'version_label' => $this->versionLabel,
            'layout_definition' => $this->layoutDefinition,
            'status' => $this->status,
        ];
    }

    /**
     * @return list<array{interpretation_range_code: string, title?: string, body: string}>
     */
    public function interpretationTexts(): array
    {
        return $this->layoutDefinition['interpretation_texts'] ?? [];
    }
}
