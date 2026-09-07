<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Contracts\Order\OrderStatus;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Inventory\Models\InventoryItem;
use Commerce\Orders\Models\OrderLineItem;
use Commerce\Product\Models\Product;

final class ProductTypeChangeGuard
{
    /**
     * @param  list<string>  $keptVariantUuids
     */
    public function assertCanBecomeSimple(Product $product, array $keptVariantUuids): void
    {
        $extraVariants = $product->variants()
            ->whereNotIn('uuid', $keptVariantUuids)
            ->get(['uuid', 'sku']);

        if ($extraVariants->isEmpty()) {
            return;
        }

        $extraVariantUuids = $extraVariants->pluck('uuid');

        $reservedVariantUuids = InventoryItem::query()
            ->whereIn('purchasable_uuid', $extraVariantUuids)
            ->where('reserved', '>', 0)
            ->pluck('purchasable_uuid');

        $orderedVariantUuids = OrderLineItem::query()
            ->join('orders', 'orders.id', '=', 'order_line_items.order_id')
            ->whereIn('order_line_items.purchasable_uuid', $extraVariantUuids)
            ->whereIn('orders.status', [
                OrderStatus::Pending->value,
                OrderStatus::Confirmed->value,
            ])
            ->pluck('order_line_items.purchasable_uuid');

        $blockingVariantUuids = $reservedVariantUuids
            ->merge($orderedVariantUuids)
            ->unique();

        if ($blockingVariantUuids->isEmpty()) {
            return;
        }

        $blockingSkus = $extraVariants
            ->whereIn('uuid', $blockingVariantUuids)
            ->pluck('sku')
            ->implode(', ');

        throw new DomainException(
            'Cannot change product to simple because these variants have reserved stock or open orders: '
            . $blockingSkus,
        );
    }

    public function assertCanBecomeVariable(Product $product): void
    {
        //
    }
}
