<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Contracts;

use TpmPsych\Domain\Instrument\Item;
use TpmPsych\Domain\Instrument\ResponseOption;
use TpmPsych\Domain\Instrument\Section;

/**
 * Aggregate repository pentru sub-entitățile unei versiuni de instrument
 * (secțiuni, itemi, opțiuni de răspuns) — sunt întotdeauna manipulate
 * împreună, în contextul unei singure InstrumentVersion.
 */
interface InstrumentStructureRepositoryInterface
{
    /** @return list<Section> */
    public function listSections(int $instrumentVersionId): array;

    public function insertSection(Section $section): Section;

    public function updateSection(Section $section): void;

    public function deleteSection(int $id): void;

    /** @return list<Item> */
    public function listItems(int $instrumentVersionId): array;

    public function findItem(int $id): ?Item;

    public function insertItem(Item $item): Item;

    public function updateItem(Item $item): void;

    public function deleteItem(int $id): void;

    /** @return list<ResponseOption> */
    public function listResponseOptions(int $itemId): array;

    public function insertResponseOption(ResponseOption $option): ResponseOption;

    public function updateResponseOption(ResponseOption $option): void;

    public function deleteResponseOption(int $id): void;
}
