<?php

declare(strict_types=1);

namespace Commerce\Cart\Support;

use Commerce\Cart\DTO\CartData;
use Commerce\Cart\DTO\ResolvedCartLineData;

final class CartCheckoutSelection
{
    public const SESSION_KEY = 'commerce.cart.checkout_selection';

    /**
     * @return list<string>|null
     */
    public static function get(): ?array
    {
        $selection = session(self::SESSION_KEY);

        if (! is_array($selection)) {
            return null;
        }

        return array_values(array_filter($selection, static fn ($uuid): bool => is_string($uuid) && $uuid !== ''));
    }

    /**
     * @param  list<string>  $purchasableUuids
     */
    public static function set(array $purchasableUuids): void
    {
        session()->put(
            self::SESSION_KEY,
            array_values(array_unique(array_filter($purchasableUuids, static fn (string $uuid): bool => $uuid !== ''))),
        );
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @param  list<ResolvedCartLineData>  $lines
     * @return list<ResolvedCartLineData>
     */
    public static function filterLines(array $lines): array
    {
        $selection = self::get();

        if ($selection === null || $selection === []) {
            return $lines;
        }

        $allowed = array_flip($selection);

        return array_values(array_filter(
            $lines,
            static fn (ResolvedCartLineData $line): bool => isset($allowed[$line->purchasableUuid]),
        ));
    }

    public static function filterCart(CartData $cart): CartData
    {
        $lines = self::filterLines($cart->lines);

        if ($lines === []) {
            return $cart;
        }

        $subtotal = array_sum(array_map(static fn (ResolvedCartLineData $line): int => $line->lineTotal, $lines));
        $itemCount = array_sum(array_map(static fn (ResolvedCartLineData $line): int => $line->quantity, $lines));

        return new CartData(
            currency: $cart->currency,
            lines: $lines,
            subtotal: $subtotal,
            itemCount: $itemCount,
            discountTotal: $cart->discountTotal,
            couponCode: $cart->couponCode,
            promotionName: $cart->promotionName,
        );
    }
}
