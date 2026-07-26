<?php

declare(strict_types=1);

namespace TpmPsych\Services;

use TpmPsych\Domain\Instrument\Item;
use TpmPsych\Domain\Instrument\ResponseOption;
use TpmPsych\Domain\Instrument\Section;
use TpmPsych\Repositories\Contracts\InstrumentStructureRepositoryInterface;
use TpmPsych\Validation\ValidationException;

/**
 * Gestionează secțiuni/itemi/opțiuni de răspuns în interiorul unei versiuni
 * de instrument. Orice mutație verifică întâi, prin InstrumentVersionService,
 * că versiunea e încă "draft" — altfel se aruncă DomainException.
 */
final class InstrumentStructureService
{
    public function __construct(
        private readonly InstrumentStructureRepositoryInterface $structure,
        private readonly InstrumentVersionService $versions,
        private readonly AuditServiceInterface $audit,
    ) {
    }

    /** @return list<Section> */
    public function listSections(int $instrumentVersionId): array
    {
        return $this->structure->listSections($instrumentVersionId);
    }

    public function addSection(int $instrumentVersionId, ?string $title, int $orderIndex, ?int $parentSectionId, int $actorUserId): Section
    {
        $this->versions->requireDraft($instrumentVersionId);

        $section = $this->structure->insertSection(new Section(
            id: null,
            instrumentVersionId: $instrumentVersionId,
            parentSectionId: $parentSectionId,
            title: $title,
            orderIndex: $orderIndex,
            branchingRule: null,
        ));

        $this->audit->log('section_created', 'section', $section->id, $actorUserId, ['instrument_version_id' => $instrumentVersionId]);

        return $section;
    }

    /** @return list<Item> */
    public function listItems(int $instrumentVersionId): array
    {
        return $this->structure->listItems($instrumentVersionId);
    }

    public function addItem(
        int $instrumentVersionId,
        ?int $sectionId,
        string $code,
        string $promptText,
        string $itemType,
        bool $isReverseScored,
        bool $isRequired,
        int $orderIndex,
        int $actorUserId
    ): Item {
        $this->versions->requireDraft($instrumentVersionId);
        $this->assertValidItemType($itemType);

        $code = trim($code);
        if ($code === '') {
            throw new ValidationException(['Codul itemului nu poate fi gol.']);
        }

        foreach ($this->structure->listItems($instrumentVersionId) as $existing) {
            if ($existing->code === $code) {
                throw new ValidationException(['Există deja un item cu codul "' . $code . '" în această versiune.']);
            }
        }

        $item = $this->structure->insertItem(new Item(
            id: null,
            instrumentVersionId: $instrumentVersionId,
            sectionId: $sectionId,
            code: $code,
            promptText: $promptText,
            itemType: $itemType,
            isReverseScored: $isReverseScored,
            isRequired: $isRequired,
            conditionalRule: null,
            orderIndex: $orderIndex,
        ));

        $this->audit->log('item_created', 'item', $item->id, $actorUserId, [
            'instrument_version_id' => $instrumentVersionId,
            'code' => $code,
        ]);

        return $item;
    }

    public function updateItem(
        int $itemId,
        string $promptText,
        bool $isReverseScored,
        bool $isRequired,
        int $orderIndex,
        int $actorUserId
    ): Item {
        $item = $this->structure->findItem($itemId);
        if ($item === null) {
            throw new ValidationException(['Itemul #' . $itemId . ' nu există.']);
        }

        $this->versions->requireDraft($item->instrumentVersionId);

        $updated = new Item(
            id: $item->id,
            instrumentVersionId: $item->instrumentVersionId,
            sectionId: $item->sectionId,
            code: $item->code,
            promptText: $promptText,
            itemType: $item->itemType,
            isReverseScored: $isReverseScored,
            isRequired: $isRequired,
            conditionalRule: $item->conditionalRule,
            orderIndex: $orderIndex,
        );

        $this->structure->updateItem($updated);
        $this->audit->log('item_updated', 'item', $itemId, $actorUserId, []);

        return $updated;
    }

    public function deleteItem(int $itemId, int $actorUserId): void
    {
        $item = $this->structure->findItem($itemId);
        if ($item === null) {
            return;
        }

        $this->versions->requireDraft($item->instrumentVersionId);
        $this->structure->deleteItem($itemId);
        $this->audit->log('item_deleted', 'item', $itemId, $actorUserId, []);
    }

    /** @return list<ResponseOption> */
    public function listResponseOptions(int $itemId): array
    {
        return $this->structure->listResponseOptions($itemId);
    }

    public function addResponseOption(int $itemId, string $labelText, float $rawValue, int $orderIndex, int $actorUserId): ResponseOption
    {
        $item = $this->structure->findItem($itemId);
        if ($item === null) {
            throw new ValidationException(['Itemul #' . $itemId . ' nu există.']);
        }

        $this->versions->requireDraft($item->instrumentVersionId);

        $option = $this->structure->insertResponseOption(new ResponseOption(
            id: null,
            itemId: $itemId,
            labelText: $labelText,
            rawValue: $rawValue,
            orderIndex: $orderIndex,
        ));

        $this->audit->log('response_option_created', 'response_option', $option->id, $actorUserId, ['item_id' => $itemId]);

        return $option;
    }

    private function assertValidItemType(string $itemType): void
    {
        if (! in_array($itemType, Item::VALID_TYPES, true)) {
            throw new ValidationException(['Tip de item necunoscut: "' . $itemType . '".']);
        }
    }
}
