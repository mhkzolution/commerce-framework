<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Cart\DTO\CartData;
use Commerce\Cart\DTO\CartLineData;
use Commerce\Cart\DTO\ResolvedCartLineData;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Pricing\PriceResolverInterface;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Contracts\Promotion\PromotionServiceInterface;
use Commerce\Contracts\Purchasable\PurchasableInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Core\Pricing\PricingContext;

final class CartService extends BaseService implements CartServiceInterface
{
    public function __construct(
        private readonly CartStorageInterface $storage,
        private readonly ProductQueryServiceInterface $productQueryService,
        private readonly InventoryQueryServiceInterface $inventoryQueryService,
        private readonly PriceResolverInterface $priceResolver,
        private readonly ?MediaQueryServiceInterface $media = null,
    ) {}

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

    public function applyCoupon(string $code): CartData
    {
        if (! app()->bound(PromotionServiceInterface::class)) {
            throw new DomainException('Promotions are not available.');
        }

        $cart = $this->resolve($this->storage->lines(), null);
        $quote = app(PromotionServiceInterface::class)->resolve($code, $cart->subtotal);

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
     * @param  list<array{purchasable_uuid: string, quantity: int}>  $rawLines
     */
    private function resolve(array $rawLines, ?string $couponCode): CartData
    {
        $resolved = [];
        $subtotal = 0;
        $itemCount = 0;
        $cartCurrency = $this->storage->currency();
        $baseCurrency = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)->baseCurrency()
            : $cartCurrency;
        $converter = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)
            : null;

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
                    channel: 'web',
                    currency: $cartCurrency,
                    quantity: $quantity,
                ),
            );
            $unitPrice = $priceQuote->getAmount();

            if ($converter !== null && $cartCurrency !== $baseCurrency) {
                $unitPrice = $converter->convert($unitPrice, $baseCurrency, $cartCurrency);
            }

            $lineTotal = $unitPrice * $quantity;
            $available = $stockLevels[$line['purchasable_uuid']]->getAvailable();
            $isPurchasable = $variant instanceof PurchasableInterface && $variant->isPurchasable();

            $product = $variant->product ?? null;
            $productName = is_string($product->name ?? null) && $product->name !== ''
                ? $product->name
                : (string) ($variant->name ?? 'Product');
            $slug = is_string($product->slug ?? null) ? $product->slug : null;

            $resolved[] = new ResolvedCartLineData(
                purchasableUuid: $line['purchasable_uuid'],
                quantity: $quantity,
                name: $productName,
                sku: $variant->sku,
                unitPrice: $unitPrice,
                lineTotal: $lineTotal,
                available: $available,
                isPurchasable: $isPurchasable,
                imageUrl: $this->lineImageUrl($variant),
                imageSrcset: $this->lineImageSrcset($variant),
                url: $slug ? route('storefront.products.show', $slug) : null,
                productName: $productName,
                variantLabel: $this->variantLabel($variant, $productName),
            );

            $subtotal += $lineTotal;
            $itemCount += $quantity;
        }

        $discountTotal = 0;
        $promotionName = null;
        $coupon = $couponCode ?? $this->storage->couponCode();

        if ($coupon !== null && app()->bound(PromotionServiceInterface::class)) {
            $promotionSubtotal = $subtotal;

            if ($converter !== null && $cartCurrency !== $baseCurrency) {
                $promotionSubtotal = $converter->convert($subtotal, $cartCurrency, $baseCurrency);
            }

            $quote = app(PromotionServiceInterface::class)->resolve($coupon, $promotionSubtotal);

            if ($quote !== null) {
                $discountTotal = (int) $quote->discount;

                if ($converter !== null && $cartCurrency !== $baseCurrency) {
                    $discountTotal = $converter->convert($discountTotal, $baseCurrency, $cartCurrency);
                }

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

    private function lineImageUrl(object $variant): ?string
    {
        if ($this->media === null) {
            return null;
        }

        $product = $variant->product ?? null;
        if ($product === null) {
            return null;
        }

        if (method_exists($product, 'loadMissing')) {
            $product->loadMissing('media');
        }

        $mediaRows = $product->media ?? collect();
        $row = $mediaRows->firstWhere('is_primary', true) ?? $mediaRows->first();
        $uuid = is_string($row?->media_uuid) ? $row->media_uuid : null;
        if ($uuid === null || $uuid === '') {
            return null;
        }

        return $this->media->getUrl($uuid, 'card')
            ?? $this->media->getUrl($uuid, 'medium')
            ?? $this->media->getUrl($uuid);
    }

    private function lineImageSrcset(object $variant): ?string
    {
        if ($this->media === null) {
            return null;
        }

        $product = $variant->product ?? null;
        if ($product === null) {
            return null;
        }

        if (method_exists($product, 'loadMissing')) {
            $product->loadMissing('media');
        }

        $mediaRows = $product->media ?? collect();
        $row = $mediaRows->firstWhere('is_primary', true) ?? $mediaRows->first();
        $uuid = is_string($row?->media_uuid) ? $row->media_uuid : null;
        if ($uuid === null || $uuid === '') {
            return null;
        }

        return $this->media->getSrcset($uuid);
    }

    private function variantLabel(object $variant, string $productName): ?string
    {
        $meta = is_array($variant->meta ?? null) ? $variant->meta : [];
        $options = $meta['options'] ?? [];

        if (is_array($options) && $options !== []) {
            $parts = [];

            foreach ($options as $key => $value) {
                if (! is_string($value) || $value === '') {
                    continue;
                }

                $parts[] = is_string($key) && ! is_numeric($key) ? "{$key}: {$value}" : $value;
            }

            if ($parts !== []) {
                return implode(' / ', $parts);
            }
        }

        $name = is_string($variant->name ?? null) ? $variant->name : null;

        if ($name !== null && $name !== '' && $name !== $productName) {
            return $name;
        }

        return null;
    }
}
