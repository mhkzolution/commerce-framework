<?php

declare(strict_types=1);

namespace Commerce\Inventory\Services;

use Commerce\Contracts\Inventory\StockLevelInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;

final class StockPolicyEvaluator
{
    public function shouldTrack(ProductVariant $variant): bool
    {
        return $variant->track_inventory;
    }

    public function canFulfill(
        Product $product,
        ProductVariant $variant,
        int $quantity,
        StockLevelInterface $level,
    ): bool {
        if (! $this->shouldTrack($variant)) {
            return true;
        }

        if ($product->backorder_policy === 'deny') {
            return $level->getAvailable() >= $quantity;
        }

        return true;
    }

    public function canConfirm(
        Product $product,
        ProductVariant $variant,
        int $quantity,
        StockLevelInterface $level,
    ): bool {
        if (! $this->shouldTrack($variant)) {
            return true;
        }

        if ($product->backorder_policy === 'deny') {
            return $level->getOnHand() >= $quantity;
        }

        return true;
    }
}
