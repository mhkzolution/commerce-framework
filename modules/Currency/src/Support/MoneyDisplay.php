<?php

declare(strict_types=1);

namespace Commerce\Currency\Support;

use Commerce\Contracts\Currency\CurrencyConverterInterface;

final class MoneyDisplay
{
    public static function format(int $minor, string $currency): string
    {
        $fallback = number_format($minor / 100, 2).' '.$currency;

        if (! app()->bound(CurrencyConverterInterface::class)) {
            return $fallback;
        }

        try {
            return app(CurrencyConverterInterface::class)->format($minor, $currency);
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
