<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class HomepageNavigationData extends DataTransferObject
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $slug,
        public ?string $url = null,
        public ?string $imageUrl = null,
        public ?int $productCount = null,
    ) {}
}
