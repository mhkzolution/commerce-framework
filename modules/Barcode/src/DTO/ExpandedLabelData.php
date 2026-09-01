<?php

declare(strict_types=1);

namespace Commerce\Barcode\DTO;

final readonly class ExpandedLabelData
{
    public function __construct(
        public string $ownerName,
        public string $barcode,
        public string $displayText,
    ) {}

    /**
     * @return array{owner_name: string, barcode: string, display_text: string}
     */
    public function toArray(): array
    {
        return [
            'owner_name' => $this->ownerName,
            'barcode' => $this->barcode,
            'display_text' => $this->displayText,
        ];
    }
}
