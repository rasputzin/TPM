<?php

declare(strict_types=1);

namespace TpmPsych\Services;

use TpmPsych\Domain\Scoring\ScoringRule;
use TpmPsych\Repositories\Contracts\InstrumentStructureRepositoryInterface;
use TpmPsych\Repositories\Contracts\ScaleRepositoryInterface;
use TpmPsych\Repositories\Contracts\ScoringRuleRepositoryInterface;
use TpmPsych\Validation\FormulaDefinitionValidator;
use TpmPsych\Validation\ValidationException;

final class ScoringRuleService
{
    public const CURRENT_ENGINE_VERSION = '1.0.0';

    public function __construct(
        private readonly ScoringRuleRepositoryInterface $rules,
        private readonly ScaleRepositoryInterface $scales,
        private readonly InstrumentStructureRepositoryInterface $structure,
        private readonly InstrumentVersionService $versions,
        private readonly FormulaDefinitionValidator $formulaValidator,
        private readonly AuditServiceInterface $audit,
    ) {
    }

    /** @return list<ScoringRule> */
    public function listByScale(int $scaleId): array
    {
        return $this->rules->listByScale($scaleId);
    }

    /**
     * @param array<string, mixed> $formulaDefinition
     */
    public function save(int $scaleId, string $ruleType, array $formulaDefinition, int $actorUserId): ScoringRule
    {
        $scale = $this->scales->find($scaleId);
        if ($scale === null) {
            throw new ValidationException(['Scala #' . $scaleId . ' nu există.']);
        }

        $this->versions->requireDraft($scale->instrumentVersionId);

        $knownItemCodes = array_map(
            static fn ($item) => $item->code,
            $this->structure->listItems($scale->instrumentVersionId)
        );

        $this->formulaValidator->validate($ruleType, $formulaDefinition, $knownItemCodes);

        $rule = $this->rules->insert(new ScoringRule(
            id: null,
            scaleId: $scaleId,
            ruleType: $ruleType,
            formulaDefinition: $formulaDefinition,
            engineVersion: self::CURRENT_ENGINE_VERSION,
            status: 'draft',
        ));

        $this->audit->log('scoring_rule_saved', 'scoring_rule', $rule->id, $actorUserId, [
            'scale_id' => $scaleId,
            'rule_type' => $ruleType,
        ]);

        return $rule;
    }
}
