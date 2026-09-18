<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

final readonly class StorefrontBrandCardData
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $url,
        public string $letter,
        public int $productCount,
        public ?string $logoUrl,
        public string $monogram,
    ) {}
}
