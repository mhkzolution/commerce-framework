<?php

declare(strict_types=1);

namespace Plugins\ProductBadge;

use Commerce\Contracts\Hook\HookRegistryInterface;
use Illuminate\Support\ServiceProvider;

final class ProductBadgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->bound(HookRegistryInterface::class)) {
            return;
        }

        $hooks = $this->app->make(HookRegistryInterface::class);

        $hooks->registerFilter('storefront.product.card', static function (mixed $html, array $context): mixed {
            $product = $context['product'] ?? null;
            $variant = $context['variant'] ?? null;
            $price = is_object($product) && isset($product->price)
                ? $product->price
                : (is_object($variant) ? ($variant->price ?? null) : null);

            if ($price === null) {
                return $html;
            }

            $badge = ((int) $price) >= 5000
                ? '<span class="inline-flex rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Premium</span>'
                : '<span class="inline-flex rounded bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Popular</span>';

            return $badge.($html ?? '');
        }, 10);
    }
}
