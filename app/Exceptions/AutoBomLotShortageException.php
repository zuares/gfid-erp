<?php

namespace App\Exceptions;

class AutoBomLotShortageException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $item = null,
        public readonly ?string $warehouse = null,
        public readonly ?string $required = null,
        public readonly ?string $available = null,
        public readonly ?string $shortage = null,
        public readonly ?int $itemId = null,
        public readonly ?float $shortageQty = null,
    ) {
        parent::__construct($message);
    }

    /** @return array<string, string|null> */
    public function flashPayload(): array
    {
        return [
            'item' => $this->item,
            'warehouse' => $this->warehouse,
            'required' => $this->required,
            'available' => $this->available,
            'shortage' => $this->shortage,
            'item_id' => $this->itemId,
            'shortage_qty' => $this->shortageQty,
            'message' => $this->getMessage(),
        ];
    }
}
