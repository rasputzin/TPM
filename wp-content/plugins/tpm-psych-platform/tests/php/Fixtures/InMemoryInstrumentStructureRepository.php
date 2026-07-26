<?php

declare(strict_types=1);

namespace TpmPsych\Tests\Fixtures;

use TpmPsych\Domain\Instrument\Item;
use TpmPsych\Domain\Instrument\ResponseOption;
use TpmPsych\Domain\Instrument\Section;
use TpmPsych\Repositories\Contracts\InstrumentStructureRepositoryInterface;

final class InMemoryInstrumentStructureRepository implements InstrumentStructureRepositoryInterface
{
    /** @var array<int, Section> */
    private array $sections = [];

    /** @var array<int, Item> */
    private array $items = [];

    /** @var array<int, ResponseOption> */
    private array $options = [];

    private int $nextSectionId = 1;

    private int $nextItemId = 1;

    private int $nextOptionId = 1;

    public function listSections(int $instrumentVersionId): array
    {
        return array_values(array_filter($this->sections, static fn (Section $s) => $s->instrumentVersionId === $instrumentVersionId));
    }

    public function insertSection(Section $section): Section
    {
        $id = $this->nextSectionId++;
        $created = new Section($id, $section->instrumentVersionId, $section->parentSectionId, $section->title, $section->orderIndex, $section->branchingRule);
        $this->sections[$id] = $created;

        return $created;
    }

    public function updateSection(Section $section): void
    {
        $this->sections[$section->id] = $section;
    }

    public function deleteSection(int $id): void
    {
        unset($this->sections[$id]);
    }

    public function listItems(int $instrumentVersionId): array
    {
        return array_values(array_filter($this->items, static fn (Item $i) => $i->instrumentVersionId === $instrumentVersionId));
    }

    public function findItem(int $id): ?Item
    {
        return $this->items[$id] ?? null;
    }

    public function insertItem(Item $item): Item
    {
        $id = $this->nextItemId++;
        $created = new Item(
            $id,
            $item->instrumentVersionId,
            $item->sectionId,
            $item->code,
            $item->promptText,
            $item->itemType,
            $item->isReverseScored,
            $item->isRequired,
            $item->conditionalRule,
            $item->orderIndex,
        );
        $this->items[$id] = $created;

        return $created;
    }

    public function updateItem(Item $item): void
    {
        $this->items[$item->id] = $item;
    }

    public function deleteItem(int $id): void
    {
        unset($this->items[$id]);
    }

    public function listResponseOptions(int $itemId): array
    {
        return array_values(array_filter($this->options, static fn (ResponseOption $o) => $o->itemId === $itemId));
    }

    public function insertResponseOption(ResponseOption $option): ResponseOption
    {
        $id = $this->nextOptionId++;
        $created = new ResponseOption($id, $option->itemId, $option->labelText, $option->rawValue, $option->orderIndex);
        $this->options[$id] = $created;

        return $created;
    }

    public function updateResponseOption(ResponseOption $option): void
    {
        $this->options[$option->id] = $option;
    }

    public function deleteResponseOption(int $id): void
    {
        unset($this->options[$id]);
    }
}
