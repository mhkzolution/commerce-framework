<?php

declare(strict_types=1);

namespace Commerce\Currency\Support;

use Commerce\Currency\DTO\CreateCurrencyData;
use Commerce\Currency\DTO\UpdateCurrencyData;

final class CurrencyFormData
{
    private const int RATE_PRECISION = 1_000_000;

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function toCreateData(array $validated): CreateCurrencyData
    {
        return new CreateCurrencyData(
            code: strtoupper((string) $validated['code']),
            name: (string) $validated['name'],
            symbol: (string) $validated['symbol'],
            decimalPlaces: (int) ($validated['decimal_places'] ?? 2),
            rateMicro: self::rateToMicro((string) $validated['rate']),
            isBase: (bool) ($validated['is_base'] ?? false),
            isActive: (bool) ($validated['is_active'] ?? true),
            sortOrder: (int) ($validated['sort_order'] ?? 0),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function toUpdateData(array $validated): UpdateCurrencyData
    {
        return new UpdateCurrencyData(
            code: strtoupper((string) $validated['code']),
            name: (string) $validated['name'],
            symbol: (string) $validated['symbol'],
            decimalPlaces: (int) ($validated['decimal_places'] ?? 2),
            rateMicro: self::rateToMicro((string) $validated['rate']),
            isBase: (bool) ($validated['is_base'] ?? false),
            isActive: (bool) ($validated['is_active'] ?? true),
            sortOrder: (int) ($validated['sort_order'] ?? 0),
        );
    }

    public static function rateFromMicro(int $rateMicro): string
    {
        return number_format($rateMicro / self::RATE_PRECISION, 6, '.', '');
    }

    private static function rateToMicro(string $rate): int
    {
        return (int) round((float) $rate * self::RATE_PRECISION);
    }
}
