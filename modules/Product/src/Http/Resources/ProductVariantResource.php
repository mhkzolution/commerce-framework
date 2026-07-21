<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Resources;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductVariant */
final class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'uuid' => $this->uuid,
            'sku' => $this->sku,
            'name' => $this->name,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'is_default' => $this->is_default,
            'is_purchasable' => $this->isPurchasable(),
        ];

        if (app()->bound(InventoryQueryServiceInterface::class)) {
            $stock = app(InventoryQueryServiceInterface::class)->getStockLevel($this->uuid);
            $data['stock'] = [
                'on_hand' => $stock->getOnHand(),
                'reserved' => $stock->getReserved(),
                'available' => $stock->getAvailable(),
            ];
        }

        return $data;
    }
}
