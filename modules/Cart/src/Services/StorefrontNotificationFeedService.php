<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Support\StorefrontMoney;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Orders\Models\Order;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;
use Commerce\Promotion\Models\Promotion;
use Commerce\Settings\Services\CustomerExperienceConfig;
use Illuminate\Support\Str;

final class StorefrontNotificationFeedService
{
    public function __construct(
        private readonly CustomerExperienceConfig $customerExperienceConfig,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        $config = $this->customerExperienceConfig->notifications();

        if (! ($config['enabled'] ?? false)) {
            return [];
        }

        $items = [];

        if ($config['newProduct'] ?? false) {
            $items = [...$items, ...$this->newProducts()];
        }

        if ($config['promotion'] ?? false) {
            $items = [...$items, ...$this->promotions()];
        }

        if ($config['lowStock'] ?? false) {
            $items = [...$items, ...$this->lowStock()];
        }

        if ($config['recentPurchase'] ?? false) {
            $items = [...$items, ...$this->recentPurchases()];
        }

        if ($config['review'] ?? false) {
            $items = [...$items, ...$this->reviews()];
        }

        return array_slice($items, 0, 8);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function newProducts(): array
    {
        return Product::query()
            ->visibleOnStorefront()
            ->with('variants')
            ->latest()
            ->limit(3)
            ->get()
            ->map(function (Product $product): array {
                $variant = $product->defaultVariant();
                $price = (float) ($variant?->price ?? 0);

                return $this->item(
                    type: 'newProduct',
                    eyebrow: __('storefront::storefront.cx_notification_new_product'),
                    title: $product->name,
                    body: StorefrontMoney::formatMajor($price, 'THB', 0),
                    action: __('storefront::storefront.cx_view_product'),
                    url: route('storefront.products.show', $product->slug),
                );
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function promotions(): array
    {
        if (! class_exists(Promotion::class)) {
            return [];
        }

        return Promotion::query()
            ->where('is_active', true)
            ->latest()
            ->limit(3)
            ->get()
            ->map(function (Promotion $promotion): array {
                $label = $promotion->type === Promotion::TYPE_PERCENTAGE
                    ? __('storefront::storefront.cx_notification_sale_percent', ['value' => (int) round($promotion->value / 100)])
                    : $promotion->name;

                return $this->item(
                    type: 'promotion',
                    eyebrow: $label,
                    title: $promotion->name,
                    body: $promotion->code ? (string) $promotion->code : null,
                    action: __('storefront::storefront.shop'),
                    url: route('storefront.shop.index'),
                );
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lowStock(): array
    {
        if (! app()->bound(InventoryQueryServiceInterface::class)) {
            return [];
        }

        $variants = ProductVariant::query()
            ->with('product')
            ->whereHas('product', static fn ($query) => $query->visibleOnStorefront())
            ->latest()
            ->limit(20)
            ->get();

        if ($variants->isEmpty()) {
            return [];
        }

        $levels = app(InventoryQueryServiceInterface::class)
            ->levelsForPurchasables($variants->pluck('uuid')->all());

        $items = [];

        foreach ($variants as $variant) {
            $available = isset($levels[$variant->uuid]) ? $levels[$variant->uuid]->getAvailable() : 0;

            if ($available <= 0 || $available > 5) {
                continue;
            }

            $product = $variant->product;

            if (! $product instanceof Product) {
                continue;
            }

            $items[] = $this->item(
                type: 'lowStock',
                eyebrow: __('storefront::storefront.cx_notification_low_stock'),
                title: $product->name,
                body: __('storefront::storefront.cx_only_left', ['count' => $available]),
                action: __('storefront::storefront.shop'),
                url: route('storefront.products.show', $product->slug),
            );

            if (count($items) >= 3) {
                break;
            }
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentPurchases(): array
    {
        if (! class_exists(Order::class)) {
            return [];
        }

        return Order::query()
            ->with('lineItems')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (Order $order): ?array {
                $line = $order->lineItems->first();

                if ($line === null) {
                    return null;
                }

                $city = is_array($order->shipping_address) ? ($order->shipping_address['city'] ?? null) : null;

                return $this->item(
                    type: 'recentPurchase',
                    eyebrow: __('storefront::storefront.cx_notification_recent_purchase'),
                    title: $city
                        ? __('storefront::storefront.cx_someone_in_city', ['city' => $city])
                        : __('storefront::storefront.cx_someone_bought'),
                    body: (string) $line->name,
                    action: null,
                    url: null,
                );
            })
            ->filter()
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function reviews(): array
    {
        return Product::query()
            ->visibleOnStorefront()
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (Product $product): ?array {
                $rating = data_get($product->meta, 'rating');
                $reviewCount = (int) data_get($product->meta, 'review_count', 0);

                if ($rating === null || $reviewCount < 1) {
                    return null;
                }

                return $this->item(
                    type: 'review',
                    eyebrow: __('storefront::storefront.cx_notification_new_review'),
                    title: $product->name,
                    body: Str::repeat('★', max(1, min(5, (int) round((float) $rating)))),
                    action: __('storefront::storefront.cx_view_product'),
                    url: route('storefront.products.show', $product->slug),
                );
            })
            ->filter()
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function item(
        string $type,
        string $eyebrow,
        string $title,
        ?string $body,
        ?string $action,
        ?string $url,
    ): array {
        return [
            'type' => $type,
            'eyebrow' => $eyebrow,
            'title' => $title,
            'body' => $body,
            'action' => $action,
            'url' => $url,
        ];
    }
}
