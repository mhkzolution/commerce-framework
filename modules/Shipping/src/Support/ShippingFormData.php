<?php

declare(strict_types=1);

namespace Commerce\Shipping\Support;

use Commerce\Shipping\DTO\CreateShippingMethodData;
use Commerce\Shipping\DTO\UpdateShippingMethodData;

final class ShippingFormData
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function toCreateData(array $validated): CreateShippingMethodData
    {
        return new CreateShippingMethodData(
            code: (string) $validated['code'],
            name: (string) $validated['name'],
            price: self::moneyToCents($validated['price']),
            description: $validated['description'] ?? null,
            freeAbove: self::optionalMoneyToCents($validated['free_above'] ?? null),
            minSubtotal: self::optionalMoneyToCents($validated['min_subtotal'] ?? null),
            maxSubtotal: self::optionalMoneyToCents($validated['max_subtotal'] ?? null),
            countries: self::parseCountries($validated['countries'] ?? null),
            isActive: (bool) ($validated['is_active'] ?? true),
            sortOrder: (int) ($validated['sort_order'] ?? 0),
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function toUpdateData(array $validated): UpdateShippingMethodData
    {
        return new UpdateShippingMethodData(
            code: (string) $validated['code'],
            name: (string) $validated['name'],
            price: self::moneyToCents($validated['price']),
            description: $validated['description'] ?? null,
            freeAbove: self::optionalMoneyToCents($validated['free_above'] ?? null),
            minSubtotal: self::optionalMoneyToCents($validated['min_subtotal'] ?? null),
            maxSubtotal: self::optionalMoneyToCents($validated['max_subtotal'] ?? null),
            countries: self::parseCountries($validated['countries'] ?? null),
            isActive: (bool) ($validated['is_active'] ?? true),
            sortOrder: (int) ($validated['sort_order'] ?? 0),
        );
    }

    private static function moneyToCents(float|int|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private static function optionalMoneyToCents(float|int|string|null $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return self::moneyToCents($amount);
    }

    /**
     * @return list<string>|null
     */
    private static function parseCountries(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $countries = array_values(array_filter(array_map(
            static fn (string $code): string => strtoupper(trim($code)),
            explode(',', $value),
        )));

        return $countries === [] ? null : $countries;
    }
}
