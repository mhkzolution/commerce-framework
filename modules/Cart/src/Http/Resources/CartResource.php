<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Resources;

use Commerce\Cart\DTO\CartData;
use Commerce\Cart\DTO\ResolvedCartLineData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CartData */
final class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CartData $cart */
        $cart = $this->resource;

        return [
            'currency' => $cart->currency,
            'subtotal' => $cart->subtotal,
            'discount_total' => $cart->discountTotal,
            'coupon_code' => $cart->couponCode,
            'promotion_name' => $cart->promotionName,
            'item_count' => $cart->itemCount,
            'lines' => array_map(static fn (ResolvedCartLineData $line): array => [
                'purchasable_uuid' => $line->purchasableUuid,
                'name' => $line->name,
                'sku' => $line->sku,
                'quantity' => $line->quantity,
                'unit_price' => $line->unitPrice,
                'line_total' => $line->lineTotal,
                'available' => $line->available,
                'is_purchasable' => $line->isPurchasable,
            ], $cart->lines),
        ];
    }
}
