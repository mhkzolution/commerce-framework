<?php

declare(strict_types=1);

namespace Commerce\Orders\Http\Resources;

use Commerce\Orders\Models\OrderLineItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderLineItem */
final class OrderLineItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'purchasable_uuid' => $this->purchasable_uuid,
            'sku' => $this->sku,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total,
        ];
    }
}
