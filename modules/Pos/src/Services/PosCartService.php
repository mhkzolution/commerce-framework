<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\DTO\CartData;
use Commerce\Cart\DTO\CartLineData;
use Commerce\Cart\DTO\ResolvedCartLineData;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Pricing\PriceResolverInterface;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Contracts\Promotion\PromotionServiceInterface;
use Commerce\Contracts\Purchasable\PurchasableInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Core\Pricing\PricingContext;
use Commerce\Pos\Support\PosCartStorage;

final class PosCartService implements CartServiceInterface
{
    public function __construct(
        private readonly PosCartStorage $storage,
        private readonly ProductQueryServiceInterface $productQueryService,
        private readonly InventoryQueryServiceInterface $inventoryQueryService,
        private readonly PriceResolverInterface $priceResolver,
    ) {}

    public function storage(): PosCartStorage
    {
        return $this->storage;
    }

    public function get(): CartData
    {
        return $this->resolve($this->storage->lines(), $this->storage->couponCode());
    }

    public function add(CartLineData $line): CartData
    {
        if ($line->quantity <= 0) {
            throw new DomainException('Quantity must be greater than zero.');
        }

        $this->assertLineIsValid($line->purchasableUuid, $line->quantity);

        $lines = $this->storage->lines();
        $found = false;

        foreach ($lines as &$existing) {
            if ($existing['purchasable_uuid'] === $line->purchasableUuid) {
                $existing['quantity'] += $line->quantity;
                $this->assertLineIsValid($line->purchasableUuid, $existing['quantity']);
                $found = true;
                break;
            }
        }
        unset($existing);

        if (! $found) {
            $lines[] = [
                'purchasable_uuid' => $line->purchasableUuid,
                'quantity' => $line->quantity,
            ];
        }

        $this->storage->put($lines);

        return $this->get();
    }

    public function update(string $purchasableUuid, int $quantity): CartData
    {
        $lines = $this->storage->lines();

        if ($quantity <= 0) {
            return $this->remove($purchasableUuid);
        }

        $this->assertLineIsValid($purchasableUuid, $quantity);

        $updated = false;
        foreach ($lines as &$line) {
            if ($line['purchasable_uuid'] === $purchasableUuid) {
                $line['quantity'] = $quantity;
                $updated = true;
                break;
            }
        }
        unset($line);

        if (! $updated) {
            throw new EntityNotFoundException("Cart line [{$purchasableUuid}] not found.");
        }

        $this->storage->put($lines);

        return $this->get();
    }

    public function remove(string $purchasableUuid): CartData
    {
        $lines = array_values(array_filter(
            $this->storage->lines(),
            static fn (array $line): bool => $line['purchasable_uuid'] !== $purchasableUuid,
        ));

        $this->storage->put($lines);

        return $this->get();
    }

    public function removePurchasedLines(array $purchasableUuids): CartData
    {
        if ($purchasableUuids === []) {
            return $this->get();
        }

        $remove = array_flip($purchasableUuids);
        $lines = array_values(array_filter(
            $this->storage->lines(),
            static fn (array $line): bool => ! isset($remove[$line['purchasable_uuid']]),
        ));

        $this->storage->put($lines);

        return $this->get();
    }

    public function setLinePriceOverride(string $purchasableUuid, ?int $unitPriceMinor): CartData
    {
        $lines = $this->storage->lines();
        $updated = false;

        foreach ($lines as &$line) {
            if ($line['purchasable_uuid'] === $purchasableUuid) {
                if ($unitPriceMinor === null || $unitPriceMinor < 0) {
                    unset($line['unit_price_override']);
                } else {
                    $line['unit_price_override'] = $unitPriceMinor;
                }
                $updated = true;
                break;
            }
        }
        unset($line);

        if (! $updated) {
            throw new EntityNotFoundException("Cart line [{$purchasableUuid}] not found.");
        }

        $this->storage->put($lines);

        return $this->get();
    }

    public function applyCoupon(string $code): CartData
    {
        if (! app()->bound(PromotionServiceInterface::class)) {
            throw new DomainException('Promotions are not available.');
        }

        $cart = $this->resolve($this->storage->lines(), null);
        $quote = app(PromotionServiceInterface::class)->resolve(trim($code), $cart->subtotal);

        if ($quote === null) {
            throw new DomainException('Invalid or expired promotion code.');
        }

        $this->storage->setCouponCode($quote->code);

        return $this->get();
    }

    public function removeCoupon(): CartData
    {
        $this->storage->setCouponCode(null);

        return $this->get();
    }

    public function clear(): void
    {
        $this->storage->clear();
    }

    public function setCurrency(string $currency): CartData
    {
        $currency = strtoupper(trim($currency));

        if (app()->bound(CurrencyConverterInterface::class)) {
            $converter = app(CurrencyConverterInterface::class);
            if (! $converter->isSupported($currency)) {
                throw new DomainException('Currency is not supported.');
            }
        }

        $this->storage->setCurrency($currency);

        return $this->get();
    }

    /**
     * @return list<array{purchasable_uuid: string, quantity: int, unit_price_override?: int, line_discount_minor?: int}>
     */
    public function rawLines(): array
    {
        return $this->storage->lines();
    }

    /**
     * @param  list<array{purchasable_uuid: string, quantity: int, unit_price_override?: int, line_discount_minor?: int}>  $lines
     */
    public function restoreRawLines(array $lines, ?string $couponCode = null): CartData
    {
        $this->storage->replaceLines($lines);
        $this->storage->setCouponCode($couponCode);

        return $this->get();
    }

    /**
     * @param  list<array{purchasable_uuid: string, quantity: int, unit_price_override?: int|null, line_discount_minor?: int|null}>  $rawLines
     */
    private function resolve(array $rawLines, ?string $couponCode): CartData
    {
        $resolved = [];
        $subtotal = 0;
        $itemCount = 0;
        $cartCurrency = $this->storage->currency();

        $uuids = array_column($rawLines, 'purchasable_uuid');
        $stockLevels = $this->inventoryQueryService->levelsForPurchasables($uuids);

        foreach ($rawLines as $line) {
            $variant = $this->productQueryService->findVariantByUuid($line['purchasable_uuid']);

            if ($variant === null) {
                continue;
            }

            $quantity = (int) $line['quantity'];
            $priceQuote = $this->priceResolver->resolve(
                $variant,
                new PricingContext(
                    channel: 'pos',
                    currency: $cartCurrency,
                    quantity: $quantity,
                ),
            );
            $unitPrice = isset($line['unit_price_override'])
                ? max(0, (int) $line['unit_price_override'])
                : $priceQuote->getAmount();

            $lineDiscount = max(0, (int) ($line['line_discount_minor'] ?? 0));
            $lineTotal = max(0, ($unitPrice * $quantity) - $lineDiscount);
            $level = $stockLevels[$line['purchasable_uuid']] ?? null;
            $available = $level !== null ? $level->getAvailable() : 0;
            $isPurchasable = $variant instanceof PurchasableInterface && $variant->isPurchasable();

            $resolved[] = new ResolvedCartLineData(
                purchasableUuid: $line['purchasable_uuid'],
                quantity: $quantity,
                name: $variant->name ?? ($variant->product->name ?? 'Product'),
                sku: $variant->sku,
                unitPrice: $unitPrice,
                lineTotal: $lineTotal,
                available: $available,
                isPurchasable: $isPurchasable,
            );

            $subtotal += $lineTotal;
            $itemCount += $quantity;
        }

        $discountTotal = 0;
        $promotionName = null;
        $coupon = $couponCode ?? $this->storage->couponCode();

        if ($coupon !== null && app()->bound(PromotionServiceInterface::class)) {
            $quote = app(PromotionServiceInterface::class)->resolve($coupon, $subtotal);

            if ($quote !== null) {
                $discountTotal = (int) $quote->discount;
                $promotionName = $quote->name;
                $coupon = $quote->code;
            } else {
                $this->storage->setCouponCode(null);
                $coupon = null;
            }
        }

        return new CartData(
            currency: $cartCurrency,
            lines: $resolved,
            subtotal: $subtotal,
            itemCount: $itemCount,
            discountTotal: $discountTotal,
            couponCode: $coupon,
            promotionName: $promotionName,
        );
    }

    private function assertLineIsValid(string $purchasableUuid, int $quantity): void
    {
        $variant = $this->productQueryService->findVariantByUuid($purchasableUuid);

        if ($variant === null) {
            throw new EntityNotFoundException("Purchasable variant [{$purchasableUuid}] not found.");
        }

        if ($variant instanceof PurchasableInterface && ! $variant->isPurchasable()) {
            throw new DomainException('This product is not available for purchase.');
        }

        if (! $this->inventoryQueryService->isAvailable($purchasableUuid, $quantity)) {
            throw new DomainException('Insufficient stock for this quantity.');
        }
    }
}
