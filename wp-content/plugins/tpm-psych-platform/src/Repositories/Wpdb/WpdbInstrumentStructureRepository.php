<?php

declare(strict_types=1);

namespace TpmPsych\Repositories\Wpdb;

use TpmPsych\Domain\Instrument\Item;
use TpmPsych\Domain\Instrument\ResponseOption;
use TpmPsych\Domain\Instrument\Section;
use TpmPsych\Repositories\Contracts\InstrumentStructureRepositoryInterface;

final class WpdbInstrumentStructureRepository implements InstrumentStructureRepositoryInterface
{
    private readonly \wpdb $db;

    private readonly string $sectionsTable;

    private readonly string $itemsTable;

    private readonly string $optionsTable;

    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db = $db ?? $wpdb;
        $this->sectionsTable = $this->db->prefix . 'tpm_sections';
        $this->itemsTable = $this->db->prefix . 'tpm_items';
        $this->optionsTable = $this->db->prefix . 'tpm_response_options';
    }

    public function listSections(int $instrumentVersionId): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->sectionsTable} WHERE instrument_version_id = %d ORDER BY order_index ASC",
                $instrumentVersionId
            ),
            ARRAY_A
        );

        return array_map(static fn (array $row) => Section::fromRow($row), $rows ?? []);
    }

    public function insertSection(Section $section): Section
    {
        $this->db->insert($this->sectionsTable, [
            'instrument_version_id' => $section->instrumentVersionId,
            'parent_section_id' => $section->parentSectionId,
            'title' => $section->title,
            'order_index' => $section->orderIndex,
            'branching_rule_json' => $section->branchingRule !== null ? wp_json_encode($section->branchingRule) : null,
        ]);

        $id = (int) $this->db->insert_id;
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->sectionsTable} WHERE id = %d", $id), ARRAY_A);

        return Section::fromRow($row);
    }

    public function updateSection(Section $section): void
    {
        $this->db->update(
            $this->sectionsTable,
            [
                'parent_section_id' => $section->parentSectionId,
                'title' => $section->title,
                'order_index' => $section->orderIndex,
                'branching_rule_json' => $section->branchingRule !== null ? wp_json_encode($section->branchingRule) : null,
            ],
            ['id' => $section->id]
        );
    }

    public function deleteSection(int $id): void
    {
        $this->db->delete($this->sectionsTable, ['id' => $id]);
    }

    public function listItems(int $instrumentVersionId): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->itemsTable} WHERE instrument_version_id = %d ORDER BY order_index ASC",
                $instrumentVersionId
            ),
            ARRAY_A
        );

        return array_map(static fn (array $row) => Item::fromRow($row), $rows ?? []);
    }

    public function findItem(int $id): ?Item
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->itemsTable} WHERE id = %d", $id), ARRAY_A);

        return $row !== null ? Item::fromRow($row) : null;
    }

    public function insertItem(Item $item): Item
    {
        $this->db->insert($this->itemsTable, [
            'instrument_version_id' => $item->instrumentVersionId,
            'section_id' => $item->sectionId,
            'code' => $item->code,
            'prompt_text' => $item->promptText,
            'item_type' => $item->itemType,
            'is_reverse_scored' => $item->isReverseScored ? 1 : 0,
            'is_required' => $item->isRequired ? 1 : 0,
            'conditional_rule_json' => $item->conditionalRule !== null ? wp_json_encode($item->conditionalRule) : null,
            'order_index' => $item->orderIndex,
        ]);

        return $this->findItem((int) $this->db->insert_id);
    }

    public function updateItem(Item $item): void
    {
        $this->db->update(
            $this->itemsTable,
            [
                'section_id' => $item->sectionId,
                'code' => $item->code,
                'prompt_text' => $item->promptText,
                'item_type' => $item->itemType,
                'is_reverse_scored' => $item->isReverseScored ? 1 : 0,
                'is_required' => $item->isRequired ? 1 : 0,
                'conditional_rule_json' => $item->conditionalRule !== null ? wp_json_encode($item->conditionalRule) : null,
                'order_index' => $item->orderIndex,
            ],
            ['id' => $item->id]
        );
    }

    public function deleteItem(int $id): void
    {
        $this->db->delete($this->itemsTable, ['id' => $id]);
    }

    public function listResponseOptions(int $itemId): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->optionsTable} WHERE item_id = %d ORDER BY order_index ASC",
                $itemId
            ),
            ARRAY_A
        );

        return array_map(static fn (array $row) => ResponseOption::fromRow($row), $rows ?? []);
    }

    public function insertResponseOption(ResponseOption $option): ResponseOption
    {
        $this->db->insert($this->optionsTable, [
            'item_id' => $option->itemId,
            'label_text' => $option->labelText,
            'raw_value' => $option->rawValue,
            'order_index' => $option->orderIndex,
        ]);

        $id = (int) $this->db->insert_id;
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->optionsTable} WHERE id = %d", $id), ARRAY_A);

        return ResponseOption::fromRow($row);
    }

    public function updateResponseOption(ResponseOption $option): void
    {
        $this->db->update(
            $this->optionsTable,
            [
                'label_text' => $option->labelText,
                'raw_value' => $option->rawValue,
                'order_index' => $option->orderIndex,
            ],
            ['id' => $option->id]
        );
    }

    public function deleteResponseOption(int $id): void
    {
        $this->db->delete($this->optionsTable, ['id' => $id]);
    }
}
