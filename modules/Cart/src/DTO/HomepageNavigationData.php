<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class HomepageNavigationData extends DataTransferObject
{
    /**
     * @param  list<self>  $children
     */
    public function __construct(
        public string $uuid,
        public string $name,
        public string $slug,
        public ?string $url = null,
        public ?string $imageUrl = null,
        public ?int $productCount = null,
        public ?string $imageSrcset = null,
        public array $children = [],
    ) {}
}
