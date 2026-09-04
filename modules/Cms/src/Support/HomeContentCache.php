<?php

declare(strict_types=1);

namespace Commerce\Cms\Support;

use Commerce\Catalog\Models\Category;
use Commerce\Cms\Models\FaqEntry;
use Commerce\Cms\Models\HeroBanner;
use Commerce\Cms\Models\HomepageSection;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Models\PromotionBanner;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Product\Models\Product;
use Illuminate\Support\Facades\Cache;

final class HomeContentCache
{
    public const PREFIX = 'storefront.home.v2';

    public static function ttl(): int
    {
        return max(60, (int) config('cms.homepage.cache_ttl', 600));
    }

    public static function remember(string $segment, callable $resolver, ?string $suffix = null): mixed
    {
        return Cache::remember(
            self::key($segment, $suffix),
            now()->addSeconds(self::ttl()),
            $resolver,
        );
    }

    public static function key(string $segment, ?string $suffix = null): string
    {
        $tenantId = app(TenantContext::class)->id();
        $base = $tenantId !== null
            ? self::PREFIX.'.'.$tenantId.'.'.$segment
            : self::PREFIX.'.'.$segment;

        if ($suffix !== null && $suffix !== '') {
            $base .= '.'.$suffix;
        }

        if ($segment === 'arrivals') {
            $base .= '.g'.self::generation('arrivals');
        }

        return $base;
    }

    public static function forget(string $segment): void
    {
        $tenantId = app(TenantContext::class)->id();
        Cache::forget(self::PREFIX.'.'.$segment);
        if ($tenantId !== null) {
            Cache::forget(self::PREFIX.'.'.$tenantId.'.'.$segment);
        }
    }

    public static function flushContent(): void
    {
        foreach (['hero', 'promotions', 'faq', 'articles', 'sections'] as $segment) {
            self::forget($segment);
        }
    }

    public static function bumpArrivals(): void
    {
        Cache::forever(self::generationKey('arrivals'), self::generation('arrivals') + 1);
    }

    public static function registerContentInvalidation(): void
    {
        $flush = static fn () => self::flushContent();

        HeroBanner::saved($flush);
        HeroBanner::deleted($flush);
        PromotionBanner::saved($flush);
        PromotionBanner::deleted($flush);
        FaqEntry::saved($flush);
        FaqEntry::deleted($flush);
        HomepageSection::saved($flush);
        HomepageSection::deleted($flush);
        Post::saved($flush);
        Post::deleted($flush);
    }

    public static function registerCatalogInvalidation(): void
    {
        $arrivals = static fn () => self::bumpArrivals();

        if (class_exists(Product::class)) {
            Product::saved($arrivals);
            Product::deleted($arrivals);
        }

        if (class_exists(Category::class)) {
            Category::saved($arrivals);
            Category::deleted($arrivals);
        }
    }

    private static function generation(string $segment): int
    {
        return (int) Cache::get(self::generationKey($segment), 1);
    }

    private static function generationKey(string $segment): string
    {
        $tenantId = app(TenantContext::class)->id();

        return $tenantId !== null
            ? self::PREFIX.'.'.$tenantId.'.'.$segment.'.generation'
            : self::PREFIX.'.'.$segment.'.generation';
    }
}
