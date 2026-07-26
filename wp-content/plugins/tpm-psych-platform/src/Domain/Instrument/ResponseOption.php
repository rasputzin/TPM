<?php

declare(strict_types=1);

namespace TpmPsych\Domain\Instrument;

final class ResponseOption
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $itemId,
        public readonly string $labelText,
        public readonly float $rawValue,
        public readonly int $orderIndex,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            itemId: (int) $row['item_id'],
            labelText: (string) $row['label_text'],
            rawValue: (float) $row['raw_value'],
            orderIndex: (int) ($row['order_index'] ?? 0),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->itemId,
            'label_text' => $this->labelText,
            'raw_value' => $this->rawValue,
            'order_index' => $this->orderIndex,
        ];
    }

    /**
     * Pentru respondent: eticheta e vizibilă, dar `raw_value` (valoarea de
     * cotare) nu se trimite niciodată către browser.
     *
     * @return array<string, mixed>
     */
    public function toRespondentPayload(): array
    {
        return [
            'id' => $this->id,
            'label_text' => $this->labelText,
            'order_index' => $this->orderIndex,
        ];
    }
}
