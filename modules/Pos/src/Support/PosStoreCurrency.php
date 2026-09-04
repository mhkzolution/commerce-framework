<?php

declare(strict_types=1);

namespace Commerce\Pos\Support;

use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;

final class PosStoreCurrency
{
    public static function resolve(): string
    {
        if (app()->bound(SettingQueryServiceInterface::class)) {
            $currency = app(SettingQueryServiceInterface::class)->get(
                'store.currency',
                config('settings.defaults.store.currency'),
            );

            if (is_string($currency) && $currency !== '') {
                return strtoupper($currency);
            }
        }

        if (app()->bound(CurrencyConverterInterface::class)) {
            return strtoupper(app(CurrencyConverterInterface::class)->baseCurrency());
        }

        return strtoupper((string) config('cart.default_currency', 'THB'));
    }
}
