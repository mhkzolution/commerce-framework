<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Orders\Models\Order;
use Commerce\Pos\Support\PosMoney;

final class PosReceiptService
{
    /** @return array<string, mixed> */
    public function build(Order $order): array
    {
        $order->loadMissing('lineItems');
        $meta = is_array($order->meta) ? $order->meta : [];
        $payments = $meta['pos_payments'] ?? [];

        $cashReceived = isset($meta['pos_cash_received']) ? (int) $meta['pos_cash_received'] : null;
        $change = isset($meta['pos_change_amount']) ? (int) $meta['pos_change_amount'] : null;

        return [
            'order_number' => $order->order_number,
            'order_uuid' => $order->uuid,
            'created_at' => $order->created_at?->format('d M Y H:i'),
            'cashier' => $meta['pos_cashier'] ?? null,
            'register' => $meta['pos_register_code'] ?? null,
            'customer_name' => $order->customer_name,
            'currency' => $order->currency,
            'lines' => $order->lineItems->map(fn ($line): array => [
                'name' => $line->name,
                'sku' => $line->sku,
                'quantity' => $line->quantity,
                'unit_price' => $this->formatMoney($line->unit_price, $order->currency),
                'line_total' => $this->formatMoney($line->line_total, $order->currency),
            ])->all(),
            'subtotal' => $this->formatMoney($order->subtotal, $order->currency),
            'discount' => $this->formatMoney($order->discount_total, $order->currency),
            'tax' => $this->formatMoney($order->tax_total, $order->currency),
            'grand_total' => $this->formatMoney($order->grand_total, $order->currency),
            'grand_total_minor' => $order->grand_total,
            'payments' => array_map(fn (array $payment): array => [
                'method' => strtoupper((string) ($payment['method'] ?? '')),
                'amount' => $this->formatMoney((int) ($payment['amount_minor'] ?? 0), $order->currency),
            ], is_array($payments) ? $payments : []),
            'change_amount' => $change !== null ? $this->formatMoney($change, $order->currency) : null,
            'cash_received' => $cashReceived !== null ? $this->formatMoney($cashReceived, $order->currency) : null,
            'notes' => $meta['pos_notes'] ?? null,
            'coupon_code' => $order->promotion_code,
        ];
    }

    private function formatMoney(int $minor, string $currency): string
    {
        if (app()->bound(CurrencyConverterInterface::class)) {
            return app(CurrencyConverterInterface::class)->format($minor, $currency);
        }

        $symbol = match (strtoupper($currency)) {
            'THB' => '฿',
            'USD' => '$',
            'EUR' => '€',
            default => strtoupper($currency).' ',
        };

        return $symbol.number_format(PosMoney::fromMinorUnits($minor), 2);
    }
}
