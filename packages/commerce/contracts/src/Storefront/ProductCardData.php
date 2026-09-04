<?php

declare(strict_types=1);

namespace Commerce\Contracts\Storefront;

final readonly class ProductCardData
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $slug,
        public string $url,
        public string $variantUuid,
        public int $price,
        public ?int $compareAtPrice,
        public ?string $imageUrl,
        public ?int $available,
        public bool $inStock,
        public ?string $secondaryImageUrl = null,
    ) {}
}
