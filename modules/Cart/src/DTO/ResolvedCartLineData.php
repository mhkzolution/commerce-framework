<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class ResolvedCartLineData extends DataTransferObject
{
    public function __construct(
        public string $purchasableUuid,
        public int $quantity,
        public string $name,
        public ?string $sku,
        public int $unitPrice,
        public int $lineTotal,
        public int $available,
        public bool $isPurchasable,
        public ?string $imageUrl = null,
        public ?string $imageSrcset = null,
        public ?string $url = null,
        public ?string $productName = null,
        public ?string $variantLabel = null,
    ) {}
}
