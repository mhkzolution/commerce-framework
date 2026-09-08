<?php

declare(strict_types=1);

namespace Commerce\Product\DTO;

use Commerce\Support\DTO\DataTransferObject;

final readonly class SuggestResult extends DataTransferObject
{
    /**
     * @param  list<SuggestHit>  $completions
     * @param  list<SuggestHit>  $products
     * @param  list<SuggestHit>  $brands
     * @param  list<SuggestHit>  $categories
     */
    public function __construct(
        public array $completions = [],
        public array $products = [],
        public array $brands = [],
        public array $categories = [],
    ) {}
}
