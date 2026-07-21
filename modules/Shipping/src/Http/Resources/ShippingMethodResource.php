<?php

declare(strict_types=1);

namespace Commerce\Shipping\Http\Resources;

use Commerce\Shipping\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShippingMethod */
final class ShippingMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'free_above' => $this->free_above,
            'min_subtotal' => $this->min_subtotal,
            'max_subtotal' => $this->max_subtotal,
            'countries' => $this->countries,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
