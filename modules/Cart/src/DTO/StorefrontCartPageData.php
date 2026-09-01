<?php

declare(strict_types=1);

namespace Commerce\Cart\DTO;

use Commerce\Product\Models\Product;
use Commerce\Support\DTO\DataTransferObject;
use Illuminate\Support\Collection;

final readonly class StorefrontCartPageData extends DataTransferObject
{
    /**
     * @param  list<StorefrontCartLineView>  $lines
     * @param  list<object>  $shippingQuotes
     * @param  array{threshold: int, remaining: int, percent: float, qualified: bool}|null  $freeShipping
     * @param  Collection<int, Product>  $completeOrderProducts
     * @param  array<string, int>  $completeOrderStockLevels
     * @param  Collection<int, Product>  $youMayAlsoLikeProducts
     * @param  array<string, int>  $youMayAlsoLikeStockLevels
     */
    public function __construct(
        public CartData $cart,
        public array $lines,
        public array $shippingQuotes,
        public ?array $freeShipping,
        public Collection $completeOrderProducts,
        public array $completeOrderStockLevels,
        public Collection $youMayAlsoLikeProducts,
        public array $youMayAlsoLikeStockLevels,
        public ?string $estimatedDelivery,
        public int $estimatedTotal,
        public int $cheapestShipping,
    ) {}

    /** @deprecated Use completeOrderProducts */
    public function recommendedProducts(): Collection
    {
        return $this->completeOrderProducts;
    }

    /** @deprecated Use completeOrderStockLevels */
    public function recommendedStockLevels(): array
    {
        return $this->completeOrderStockLevels;
    }
}
