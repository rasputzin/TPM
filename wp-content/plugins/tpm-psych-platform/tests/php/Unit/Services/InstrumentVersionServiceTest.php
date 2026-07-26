<?php

declare(strict_types=1);

namespace TpmPsych\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use TpmPsych\Domain\Instrument\InstrumentVersion;
use TpmPsych\Services\InstrumentVersionService;
use TpmPsych\Support\DomainException;
use TpmPsych\Tests\Fixtures\InMemoryAuditService;
use TpmPsych\Tests\Fixtures\InMemoryInstrumentStructureRepository;
use TpmPsych\Tests\Fixtures\InMemoryInstrumentVersionRepository;
use TpmPsych\Tests\Fixtures\InMemoryScaleRepository;
use TpmPsych\Tests\Fixtures\InMemoryScoringRuleRepository;
use TpmPsych\Validation\ValidationException;

/**
 * Verifică regula centrală de versionare imutabilă: o versiune publicată
 * (status != draft) nu mai poate fi modificată — orice corecție necesită o
 * versiune nouă. O evaluare istorică rămâne astfel legată de exact
 * versiunea folosită la momentul administrării.
 */
final class InstrumentVersionServiceTest extends TestCase
{
    private InstrumentVersionService $service;

    private InMemoryInstrumentVersionRepository $versions;

    private InMemoryInstrumentStructureRepository $structure;

    private InMemoryScaleRepository $scales;

    private InMemoryScoringRuleRepository $scoringRules;

    private InMemoryAuditService $audit;

    protected function setUp(): void
    {
        $this->versions = new InMemoryInstrumentVersionRepository();
        $this->structure = new InMemoryInstrumentStructureRepository();
        $this->scales = new InMemoryScaleRepository();
        $this->scoringRules = new InMemoryScoringRuleRepository();
        $this->audit = new InMemoryAuditService();

        $this->service = new InstrumentVersionService(
            $this->versions,
            $this->structure,
            $this->scales,
            $this->scoringRules,
            $this->audit,
        );
    }

    public function testRequireDraftPassesForDraftVersion(): void
    {
        $draft = $this->service->createEmptyDraft(instrumentId: 1, versionLabel: '1.0.0', language: 'ro', actorUserId: 1);

        $result = $this->service->requireDraft($draft->id);

        self::assertSame($draft->id, $result->id);
    }

    public function testRequireDraftThrowsForActiveVersion(): void
    {
        $draft = $this->buildPublishableDraft();
        $this->service->publish($draft->id, actorUserId: 1);

        $this->expectException(DomainException::class);
        $this->service->requireDraft($draft->id);
    }

    public function testPublishDeprecatesPreviousActiveVersion(): void
    {
        $first = $this->buildPublishableDraft('1.0.0');
        $this->service->publish($first->id, actorUserId: 1);

        $second = $this->service->cloneAsDraft($first->id, '1.1.0', actorUserId: 1);
        $this->service->publish($second->id, actorUserId: 1);

        $firstReloaded = $this->service->find($first->id);
        $secondReloaded = $this->service->find($second->id);

        self::assertSame(InstrumentVersion::STATUS_DEPRECATED, $firstReloaded->status);
        self::assertSame(InstrumentVersion::STATUS_ACTIVE, $secondReloaded->status);
    }

    public function testPublishRejectsVersionWithoutItems(): void
    {
        $draft = $this->service->createEmptyDraft(instrumentId: 5, versionLabel: '1.0.0', language: 'ro', actorUserId: 1);

        $this->expectException(ValidationException::class);
        $this->service->publish($draft->id, actorUserId: 1);
    }

    public function testCloneAsDraftCopiesItemsAndScales(): void
    {
        $first = $this->buildPublishableDraft('1.0.0');

        $clone = $this->service->cloneAsDraft($first->id, '2.0.0', actorUserId: 1);

        self::assertCount(2, $this->structure->listItems($clone->id));
        self::assertCount(1, $this->scales->listByInstrumentVersion($clone->id));
        self::assertSame(InstrumentVersion::STATUS_DRAFT, $clone->status);
    }

    public function testAuditLogRecordsPublishEvent(): void
    {
        $draft = $this->buildPublishableDraft();
        $this->service->publish($draft->id, actorUserId: 42);

        $publishedEvents = array_filter($this->audit->events, static fn (array $e) => $e['event_type'] === 'instrument_version_published');

        self::assertCount(1, $publishedEvents);
    }

    private function buildPublishableDraft(string $versionLabel = '1.0.0'): InstrumentVersion
    {
        $draft = $this->service->createEmptyDraft(instrumentId: 1, versionLabel: $versionLabel, language: 'ro', actorUserId: 1);

        $this->structure->insertItem(new \TpmPsych\Domain\Instrument\Item(
            null, $draft->id, null, 'D1', 'Item demonstrativ 1', 'numeric', false, true, null, 0
        ));
        $this->structure->insertItem(new \TpmPsych\Domain\Instrument\Item(
            null, $draft->id, null, 'D2', 'Item demonstrativ 2', 'numeric', false, true, null, 1
        ));

        $scale = $this->scales->insert(new \TpmPsych\Domain\Instrument\Scale(null, $draft->id, null, 'TOTAL', 'Scală demonstrativă'));

        $this->scoringRules->insert(new \TpmPsych\Domain\Scoring\ScoringRule(
            null, $scale->id, 'sum', ['item_codes' => ['D1', 'D2']], '1.0.0', 'draft'
        ));

        return $draft;
    }
}
