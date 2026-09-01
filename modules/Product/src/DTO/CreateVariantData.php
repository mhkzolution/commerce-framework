<?php

declare(strict_types=1);

namespace Commerce\Product\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class CreateVariantData extends DataTransferObject
{
    public function __construct(
        public string $productUuid,
        public ?string $sku = null,
        public ?string $name = null,
        public float $price = 0,
        public ?float $compareAtPrice = null,
        public bool $isDefault = false,
        public int $position = 0,
    ) {}
}
