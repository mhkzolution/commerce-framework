<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

final readonly class ShopFilterCatalog
{
    /**
     * @param  list<array{name: string, slug: string}>  $brands
     * @param  list<array{label: string, min: ?int, max: ?int}>  $pricePresets
     * @param  list<string>  $sizes
     * @param  list<string>  $colors
     * @param  list<int>  $sizeAttributeIds
     * @param  list<int>  $colorAttributeIds
     */
    public function __construct(
        public array $brands = [],
        public array $pricePresets = [],
        public array $sizes = [],
        public array $colors = [],
        public array $sizeAttributeIds = [],
        public array $colorAttributeIds = [],
    ) {}
}
