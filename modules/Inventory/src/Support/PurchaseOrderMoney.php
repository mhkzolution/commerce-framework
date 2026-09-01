<?php

declare(strict_types=1);

namespace Commerce\Inventory\Support;

use Commerce\Contracts\Currency\CurrencyConverterInterface;

final class PurchaseOrderMoney
{
    public function defaultCurrency(): string
    {
        if (app()->bound(CurrencyConverterInterface::class)) {
            return app(CurrencyConverterInterface::class)->baseCurrency();
        }

        return strtoupper((string) config('orders.default_currency', 'USD'));
    }

    public function format(float $amount, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?? $this->defaultCurrency());

        if (app()->bound(CurrencyConverterInterface::class)) {
            $minor = (int) round($amount * 100);

            return app(CurrencyConverterInterface::class)->format($minor, $currency);
        }

        return number_format($amount, 2).' '.$currency;
    }

    public function toBase(float $amount, ?string $currency = null): float
    {
        $currency = strtoupper($currency ?? $this->defaultCurrency());

        if (! app()->bound(CurrencyConverterInterface::class)) {
            return $amount;
        }

        $converter = app(CurrencyConverterInterface::class);
        $base = strtoupper($converter->baseCurrency());

        if ($currency === $base) {
            return $amount;
        }

        $minor = (int) round($amount * 100);

        return $converter->convert($minor, $currency, $base) / 100;
    }

    /**
     * @return list<string>
     */
    public function activeCurrencyCodes(): array
    {
        if (! app()->bound(CurrencyConverterInterface::class)) {
            return [$this->defaultCurrency()];
        }

        return array_map(
            static fn (object $currency): string => (string) $currency->code,
            app(CurrencyConverterInterface::class)->activeCurrencies(),
        );
    }
}
