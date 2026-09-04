<?php

declare(strict_types=1);

namespace Commerce\Product\Support;

final class ProductPrice
{
    public static function toMinorUnits(float|string|int|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) round(((float) $amount) * 100);
    }

    public static function fromMinorUnits(int $amount): float
    {
        return round($amount / 100, 2);
    }

    public static function normalize(float|string|int|null $amount): float
    {
        if ($amount === null || $amount === '') {
            return 0.0;
        }

        return round((float) $amount, 2);
    }
}
