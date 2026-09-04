<?php

declare(strict_types=1);

namespace Commerce\Contracts\Storefront;

final readonly class ProductDetailData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $imageUrl,
        public int $price,
        public ?int $compareAtPrice,
        public string $displayCurrency,
        public ?string $sku,
        public ?int $available,
        public bool $inStock,
        public string $variantUuid,
        public string $shopUrl,
    ) {}
}
