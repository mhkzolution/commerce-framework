<?php

declare(strict_types=1);

namespace Plugins\ProductBadge;

use Commerce\Contracts\Hook\HookRegistryInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
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
            $createdAt = is_object($product) ? ($product->createdAt ?? null) : null;

            if (! $createdAt instanceof DateTimeInterface) {
                return $html;
            }

            if (Carbon::parse($createdAt)->startOfDay()->lt(now()->startOfDay()->subDays(14))) {
                return $html;
            }

            $label = e(__('storefront::storefront.new_badge'));

            return '<span class="storefront-product-card__badge">'.$label.'</span>'.($html ?? '');
        }, 10);
    }
}
