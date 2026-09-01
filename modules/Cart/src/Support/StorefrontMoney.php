<?php

declare(strict_types=1);

namespace Commerce\Cart\Support;

use Commerce\Contracts\Currency\CurrencyConverterInterface;

final class StorefrontMoney
{
    public static function formatMinor(int $amountMinor, string $currency): string
    {
        $currency = strtoupper($currency);

        if (app()->bound(CurrencyConverterInterface::class)) {
            return self::ensureSymbolSpacing(
                app(CurrencyConverterInterface::class)->format($amountMinor, $currency),
                $currency,
            );
        }

        $meta = self::meta($currency);

        return self::compose($meta['symbol'], $amountMinor / (10 ** $meta['decimals']), $meta['decimals']);
    }

    public static function formatMajor(float $amount, string $currency, ?int $decimals = null): string
    {
        $meta = self::meta($currency);
        $places = $decimals ?? $meta['decimals'];

        return self::compose($meta['symbol'], $amount, $places);
    }

    /**
     * @return array{code: string, symbol: string, decimals: int}
     */
    public static function meta(string $currency): array
    {
        $currency = strtoupper($currency);

        if (app()->bound(CurrencyConverterInterface::class)) {
            foreach (app(CurrencyConverterInterface::class)->activeCurrencies() as $activeCurrency) {
                if (strtoupper((string) $activeCurrency->code) === $currency) {
                    return [
                        'code' => $currency,
                        'symbol' => (string) $activeCurrency->symbol,
                        'decimals' => (int) $activeCurrency->decimal_places,
                    ];
                }
            }
        }

        return match ($currency) {
            'THB' => ['code' => 'THB', 'symbol' => '฿', 'decimals' => 2],
            'USD' => ['code' => 'USD', 'symbol' => '$', 'decimals' => 2],
            'EUR' => ['code' => 'EUR', 'symbol' => '€', 'decimals' => 2],
            default => ['code' => $currency, 'symbol' => $currency, 'decimals' => 2],
        };
    }

    /**
     * @return array{currency: string, symbol: string, decimals: int}
     */
    public static function jsPayload(?string $currency = null): array
    {
        $currency = strtoupper($currency ?? (string) config('cart.default_currency', 'THB'));
        $meta = self::meta($currency);

        return [
            'currency' => $meta['code'],
            'symbol' => $meta['symbol'],
            'decimals' => $meta['decimals'],
        ];
    }

    private static function compose(string $symbol, float $amount, int $decimals): string
    {
        return trim($symbol).' '.number_format($amount, $decimals);
    }

    private static function ensureSymbolSpacing(string $formatted, string $currency): string
    {
        $symbol = self::meta($currency)['symbol'];

        if ($symbol !== '' && str_starts_with($formatted, $symbol) && ! str_starts_with($formatted, $symbol.' ')) {
            return $symbol.' '.ltrim(substr($formatted, strlen($symbol)));
        }

        return $formatted;
    }
}
