<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Product\Models\Product;
use Commerce\Settings\Services\CustomerExperienceConfig;

final class StorefrontNotificationFeedService
{
    private const NEW_PRODUCT_WINDOW_DAYS = 7;

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

        if (! ($config['newProduct'] ?? false)) {
            return [];
        }

        return Product::query()
            ->visibleOnStorefront()
            ->with('variants')
            ->where(function ($query): void {
                $cutoff = now()->subDays(self::NEW_PRODUCT_WINDOW_DAYS);
                $query->where('published_at', '>=', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff): void {
                        $inner->whereNull('published_at')
                            ->where('created_at', '>=', $cutoff);
                    });
            })
            ->latest()
            ->limit(3)
            ->get()
            ->map(function (Product $product): array {
                $price = (int) ($product->defaultVariant()?->price ?? 0);

                return [
                    'id' => 'newProduct:'.$product->uuid,
                    'type' => 'newProduct',
                    'eyebrow' => __('storefront::storefront.cx_notification_new_product'),
                    'title' => $product->name,
                    'body' => number_format($price / 100, 2),
                    'action' => __('storefront::storefront.cx_view_product'),
                    'url' => route('storefront.products.show', $product->slug),
                ];
            })
            ->all();
    }
}
