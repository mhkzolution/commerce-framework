<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class HomepageProductCardData extends DataTransferObject
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
    ) {}
}
