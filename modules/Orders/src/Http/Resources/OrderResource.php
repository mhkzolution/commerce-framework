<?php

declare(strict_types=1);

namespace Commerce\Orders\Http\Resources;

use Commerce\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'promotion_uuid' => $this->promotion_uuid,
            'promotion_code' => $this->promotion_code,
            'tax_total' => $this->tax_total,
            'shipping_total' => $this->shipping_total,
            'shipping_method_uuid' => $this->shipping_method_uuid,
            'shipping_method_name' => $this->shipping_method_name,
            'grand_total' => $this->grand_total,
            'customer_uuid' => $this->customer_uuid,
            'customer_email' => $this->customer_email,
            'customer_name' => $this->customer_name,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'channel' => $this->channel,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'line_items' => OrderLineItemResource::collection($this->whenLoaded('lineItems')),
        ];
    }
}
