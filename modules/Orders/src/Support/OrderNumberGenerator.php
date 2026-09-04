<?php

declare(strict_types=1);

namespace Commerce\Orders\Support;

use Commerce\Orders\Models\Order;

final class OrderNumberGenerator
{
    public static function next(): string
    {
        $prefix = (string) config('orders.order_number_prefix', 'ORD-');
        $lastNumber = Order::query()
            ->withTrashed()
            ->where('order_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('order_number');

        $sequence = 1;
        if (is_string($lastNumber) && str_starts_with($lastNumber, $prefix)) {
            $sequence = max(1, (int) substr($lastNumber, strlen($prefix)) + 1);
        }

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
