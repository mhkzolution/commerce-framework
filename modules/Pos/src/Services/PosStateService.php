<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Cart\DTO\CartData;
use Commerce\Cart\DTO\ResolvedCartLineData;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Customer\CustomerQueryServiceInterface;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Tax\TaxQuoteServiceInterface;
use Commerce\Pos\Models\Register;
use Commerce\Pos\Models\Session;
use Commerce\Pos\Support\PosMoney;
use Commerce\Pos\Support\PosSessionState;
use Commerce\Pos\Support\PosStoreCurrency;
use Commerce\Product\Models\ProductVariant;

final class PosStateService
{
    public function __construct(
        private readonly PosSaleService $saleService,
        private readonly InventoryQueryServiceInterface $inventoryQueryService,
        private readonly PosProductImageService $imageService,
    ) {}

    /** @return array<string, mixed> */
    public function build(Register $register, ?Session $session, PosSessionState $state, string $syncStatus = 'synced'): array
    {
        $cartService = $this->saleService->cart($register);
        $cart = $cartService->get();
        $customer = $this->resolveCustomer($state);
        $totals = $this->buildTotals($cart);
        $rawLines = $cartService->rawLines();
        $rawByUuid = collect($rawLines)->keyBy('purchasable_uuid');
        $imageMap = $this->imageMapForCart($cart);

        return [
            'register_uuid' => $register->uuid,
            'session_uuid' => $session?->uuid,
            'context' => [
                'cashier' => (string) auth()->user()?->name,
                'branch' => $register->location ?: 'สาขาหลัก',
                'register' => $register->code.' · '.$register->name,
                'shift' => $session !== null
                    ? 'เปิด · '.$session->opened_at?->format('H:i')
                    : 'ยังไม่เปิดกะ',
                'network_status' => 'online',
                'sync_status' => $syncStatus,
            ],
            'cart' => [
                'lines' => array_map(
                    fn (ResolvedCartLineData $line): array => $this->presentCartLine(
                        $line,
                        $rawByUuid->get($line->purchasableUuid, []),
                        $imageMap[$line->purchasableUuid] ?? null,
                        $cart->currency,
                    ),
                    $cart->lines,
                ),
                'item_count' => $cart->itemCount,
                'currency' => $cart->currency,
                'coupon_code' => $cart->couponCode,
                'promotion_name' => $cart->promotionName,
            ],
            'customer' => $customer,
            'totals' => $totals,
            'payment' => [
                'method' => $state->paymentMethod(),
                'status' => $cart->lines === [] ? 'idle' : 'unpaid',
                'mixed_payments' => $state->mixedPayments(),
                'change_amount' => null,
            ],
            'notes' => $state->notes(),
            'holds' => app(PosHeldSaleService::class)->list($register->uuid),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function searchProducts(string $query, int $limit = 20): array
    {
        $variants = $this->saleService->searchProducts($query, $limit);
        $uuids = array_map(static fn (ProductVariant $v): string => $v->uuid, $variants);
        $stockLevels = $this->inventoryQueryService->levelsForPurchasables($uuids);
        $imageMap = $this->imageService->mapForVariants($variants);

        $currency = PosStoreCurrency::resolve();

        return array_map(function (ProductVariant $variant) use ($stockLevels, $imageMap, $currency): array {
            $level = $stockLevels[$variant->uuid] ?? null;
            $available = $level !== null ? $level->getAvailable() : 0;
            $attributes = [];

            if ($variant->name && $variant->product?->name && $variant->name !== $variant->product->name) {
                $attributes[] = $variant->name;
            }

            return [
                'id' => $variant->uuid,
                'uuid' => $variant->uuid,
                'image_url' => $imageMap[$variant->uuid] ?? null,
                'name' => $variant->name ?: ($variant->product?->name ?? 'Product'),
                'sku' => $variant->sku,
                'stock' => $available,
                'price' => $this->formatMoney((int) $variant->price, $currency),
                'price_minor' => (int) $variant->price,
                'attributes' => $attributes,
                'stock_warning' => $this->stockWarning($available),
            ];
        }, $variants);
    }

    /**
     * @param  array<string, mixed>  $rawLine
     * @return array<string, mixed>
     */
    private function presentCartLine(ResolvedCartLineData $line, array $rawLine, ?string $imageUrl, string $currency): array
    {
        $hasOverride = isset($rawLine['unit_price_override']);

        return [
            'id' => $line->purchasableUuid,
            'purchasable_uuid' => $line->purchasableUuid,
            'image_url' => $imageUrl,
            'name' => $line->name,
            'variant' => $line->sku ?? '',
            'quantity' => $line->quantity,
            'unit_price' => $this->formatMoney($line->unitPrice, $currency),
            'unit_price_minor' => $line->unitPrice,
            'price_overridden' => $hasOverride,
            'discount' => isset($rawLine['line_discount_minor']) && $rawLine['line_discount_minor'] > 0
                ? $this->formatMoney((int) $rawLine['line_discount_minor'], $currency)
                : '',
            'subtotal' => $this->formatMoney($line->lineTotal, $currency),
            'subtotal_minor' => $line->lineTotal,
            'available' => $line->available,
            'stock_warning' => $this->lineStockWarning($line),
            'is_purchasable' => $line->isPurchasable,
        ];
    }

    /** @return array<string, string|null> */
    private function imageMapForCart(CartData $cart): array
    {
        $map = [];

        foreach ($cart->lines as $line) {
            $variant = ProductVariant::query()->with('product.media')->where('uuid', $line->purchasableUuid)->first();
            if ($variant !== null) {
                $map[$line->purchasableUuid] = $this->imageService->forVariant($variant);
            }
        }

        return $map;
    }

    /** @return array<string, mixed> */
    private function resolveCustomer(PosSessionState $state): array
    {
        $uuid = $state->customerUuid();

        if ($uuid === null || ! app()->bound(CustomerQueryServiceInterface::class)) {
            return [
                'is_guest' => true,
                'customer' => null,
                'reward_points' => null,
                'tier' => null,
                'has_special_pricing' => false,
            ];
        }

        $customer = app(CustomerQueryServiceInterface::class)->findByUuid($uuid);

        if ($customer === null) {
            return [
                'is_guest' => true,
                'customer' => null,
                'reward_points' => null,
                'tier' => null,
                'has_special_pricing' => false,
            ];
        }

        $meta = is_object($customer) && isset($customer->meta) ? (array) $customer->meta : [];

        return [
            'is_guest' => false,
            'customer' => [
                'uuid' => $customer->uuid,
                'name' => $customer->name,
                'phone' => $customer->phone ?? null,
                'email' => $customer->email ?? null,
            ],
            'reward_points' => isset($meta['reward_points']) ? (int) $meta['reward_points'] : null,
            'tier' => $meta['tier'] ?? null,
            'has_special_pricing' => (bool) ($meta['special_pricing'] ?? false),
        ];
    }

    /** @return array<string, string|int> */
    private function buildTotals(CartData $cart): array
    {
        $taxable = max(0, $cart->subtotal - $cart->discountTotal);
        $taxTotal = 0;

        if (app()->bound(TaxQuoteServiceInterface::class) && $taxable > 0) {
            $quote = app(TaxQuoteServiceInterface::class)->calculate($taxable, null, $cart->currency);
            $taxTotal = (int) ($quote->total ?? 0);
        }

        $grandTotal = max(0, $taxable + $taxTotal);

        return [
            'subtotal' => $this->formatMoney($cart->subtotal, $cart->currency),
            'subtotal_minor' => $cart->subtotal,
            'discount' => $this->formatMoney($cart->discountTotal, $cart->currency),
            'discount_minor' => $cart->discountTotal,
            'tax' => $this->formatMoney($taxTotal, $cart->currency),
            'tax_minor' => $taxTotal,
            'shipping' => $this->formatMoney(0, $cart->currency),
            'shipping_minor' => 0,
            'grand_total' => $this->formatMoney($grandTotal, $cart->currency),
            'grand_total_minor' => $grandTotal,
            'currency' => $cart->currency,
            'currency_symbol' => $this->currencySymbol($cart->currency),
        ];
    }

    private function formatMoney(int $minor, string $currency = 'THB'): string
    {
        $currency = strtoupper($currency);

        if (app()->bound(CurrencyConverterInterface::class)) {
            return app(CurrencyConverterInterface::class)->format($minor, $currency);
        }

        return $this->currencySymbol($currency).number_format(PosMoney::fromMinorUnits($minor), 2);
    }

    private function currencySymbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'THB' => '฿',
            'USD' => '$',
            'EUR' => '€',
            default => strtoupper($currency).' ',
        };
    }

    private function stockWarning(int $available): ?string
    {
        if ($available <= 0) {
            return 'out';
        }

        if ($available <= 5) {
            return 'low';
        }

        return null;
    }

    private function lineStockWarning(ResolvedCartLineData $line): ?string
    {
        if (! $line->isPurchasable) {
            return 'Product unavailable';
        }

        if ($line->available <= 0) {
            return 'Out of stock';
        }

        if ($line->quantity > $line->available) {
            return "Only {$line->available} in stock";
        }

        if ($line->available <= 5) {
            return 'Low stock';
        }

        return null;
    }
}
