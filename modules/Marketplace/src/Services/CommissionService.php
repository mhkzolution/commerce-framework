<?php

declare(strict_types=1);

namespace Commerce\Marketplace\Services;

use Commerce\Marketplace\Models\Commission;
use Commerce\Marketplace\Models\Seller;
use Commerce\Orders\Models\Order;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

final class CommissionService
{
    public function recordForOrder(Order $order): void
    {
        $order->loadMissing('lineItems');

        DB::transaction(function () use ($order): void {
            foreach ($order->lineItems as $line) {
                $variant = ProductVariant::query()
                    ->with('product')
                    ->where('uuid', $line->purchasable_uuid)
                    ->first();

                $sellerUuid = $variant?->product?->seller_uuid;

                if ($sellerUuid === null) {
                    continue;
                }

                $seller = Seller::query()->where('uuid', $sellerUuid)->first();

                if ($seller === null || $seller->status !== 'active') {
                    continue;
                }

                if (Commission::query()->where('order_line_item_uuid', $line->uuid)->exists()) {
                    continue;
                }

                $commissionAmount = (int) round($line->line_total * ($seller->commission_rate / 10000));

                Commission::query()->create([
                    'order_uuid' => $order->uuid,
                    'order_line_item_uuid' => $line->uuid,
                    'seller_uuid' => $seller->uuid,
                    'line_total' => $line->line_total,
                    'commission_rate' => $seller->commission_rate,
                    'commission_amount' => $commissionAmount,
                    'status' => 'pending',
                ]);
            }
        });
    }
}
