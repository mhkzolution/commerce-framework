<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Commerce\Core\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;

final class StorefrontNavigationCatalog
{
    private const CACHE_PREFIX = 'storefront.navigation.v3';

    /** @var SupportCollection<int, Category>|null */
    private ?SupportCollection $categories = null;

    /** @var SupportCollection<int, Collection>|null */
    private ?SupportCollection $collections = null;

    /** @var SupportCollection<int, Brand>|null */
    private ?SupportCollection $brands = null;

    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * @return SupportCollection<int, Category>
     */
    public function categories(): SupportCollection
    {
        return $this->categories ??= $this->remember(
            'categories',
            Category::class,
            static fn () => Category::query()->forStorefrontNavigation()->get(),
        );
    }

    /**
     * @return SupportCollection<int, Collection>
     */
    public function collections(): SupportCollection
    {
        return $this->collections ??= $this->remember(
            'collections',
            Collection::class,
            static fn () => Collection::query()->forStorefrontNavigation()->get(),
        );
    }

    /**
     * @return SupportCollection<int, Brand>
     */
    public function brands(): SupportCollection
    {
        return $this->brands ??= $this->remember(
            'brands',
            Brand::class,
            static fn () => Brand::query()->forStorefrontNavigation()->get(),
        );
    }

    public static function forgetCache(?int $tenantId = null): void
    {
        $tenantId ??= app(TenantContext::class)->id();

        foreach (['categories', 'collections', 'brands'] as $segment) {
            Cache::forget(self::cacheKeyFor($segment));
            if ($tenantId !== null) {
                Cache::forget(self::cacheKeyFor($segment, $tenantId));
            }
        }
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $modelClass
     * @param  callable(): SupportCollection<int, T>  $resolver
     * @return SupportCollection<int, T>
     */
    private function remember(string $segment, string $modelClass, callable $resolver): SupportCollection
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = Cache::remember(
            self::cacheKeyFor($segment, $this->tenantContext->id()),
            now()->addSeconds((int) config('cart.storefront.navigation_cache_ttl', 3600)),
            static function () use ($resolver): array {
                return $resolver()
                    ->map(static fn (Model $model): array => $model->getAttributes())
                    ->values()
                    ->all();
            },
        );

        /** @var SupportCollection<int, T> */
        return $modelClass::hydrate($rows)
            ->filter(static fn (Model $model): bool => filled($model->getAttribute('slug')))
            ->values();
    }

    private static function cacheKeyFor(string $segment, ?int $tenantId = null): string
    {
        $tenantId ??= app(TenantContext::class)->id();

        return $tenantId !== null
            ? self::CACHE_PREFIX.".{$tenantId}.{$segment}"
            : self::CACHE_PREFIX.".{$segment}";
    }
}
