<?php

declare(strict_types=1);

namespace Commerce\Orders\Support;

final class OrderFinancialStatus
{
    public const UNPAID = 'unpaid';

    public const PENDING = 'pending';

    public const PARTIALLY_PAID = 'partially_paid';

    public const PAID = 'paid';

    public const PARTIALLY_REFUNDED = 'partially_refunded';

    public const REFUNDED = 'refunded';

    /**
     * @param  iterable<int, object>  $payments
     */
    public static function fromPayments(int $grandTotal, iterable $payments): string
    {
        $paid = 0;
        $refunded = 0;
        $hasPending = false;

        foreach ($payments as $payment) {
            $status = (string) ($payment->status ?? '');
            $amount = (int) ($payment->amount ?? 0);
            $meta = is_array($payment->meta ?? null) ? $payment->meta : [];

            if ($status === 'paid') {
                $paid += $amount;
            } elseif ($status === 'refunded') {
                $refunded += (int) ($meta['refund_amount'] ?? $amount);
            } elseif ($status === 'pending') {
                $hasPending = true;
            }
        }

        if ($refunded > 0 && $paid === 0) {
            return ($grandTotal > 0 && $refunded >= $grandTotal)
                ? self::REFUNDED
                : self::PARTIALLY_REFUNDED;
        }

        if ($refunded > 0) {
            return self::PARTIALLY_REFUNDED;
        }

        if ($grandTotal > 0 && $paid >= $grandTotal) {
            return self::PAID;
        }

        if ($paid > 0) {
            return self::PARTIALLY_PAID;
        }

        if ($hasPending) {
            return self::PENDING;
        }

        return self::UNPAID;
    }
}
