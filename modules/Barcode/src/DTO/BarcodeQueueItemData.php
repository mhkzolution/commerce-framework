<?php

declare(strict_types=1);

namespace Commerce\Barcode\DTO;

use Commerce\Barcode\Enums\BarcodeQueueSource;

final readonly class BarcodeQueueItemData
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public BarcodeQueueSource $source,
        public string $title,
        public string $barcode,
        public string $displayText,
        public string $ownerName,
        public int $quantity = 1,
        public ?string $thumbnailUrl = null,
        public ?string $variantId = null,
        public ?string $productId = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source->value,
            'title' => $this->title,
            'barcode' => $this->barcode,
            'display_text' => $this->displayText,
            'owner_name' => $this->ownerName,
            'quantity' => $this->quantity,
            'thumbnail_url' => $this->thumbnailUrl,
            'variant_id' => $this->variantId,
            'product_id' => $this->productId,
            'meta' => $this->meta,
        ];
    }
}
