<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

final readonly class ShopFilterCatalog
{
    /**
     * @param  list<array{name: string, slug: string, count: int}>  $brands
     * @param  list<array{label: string, min: ?int, max: ?int}>  $pricePresets
     * @param  list<array{code: string, name: string, values: list<array{code: string, label: string, count: int}>}>  $facets
     */
    public function __construct(
        public array $brands = [],
        public array $pricePresets = [],
        public array $facets = [],
    ) {}
}
