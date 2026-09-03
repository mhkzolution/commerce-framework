<?php

declare(strict_types=1);

namespace Commerce\Contracts\Storefront;

final readonly class HeaderBrandData
{
    public function __construct(
        public string $name,
        public ?string $logoUrl,
        public string $homeUrl,
    ) {}
}
