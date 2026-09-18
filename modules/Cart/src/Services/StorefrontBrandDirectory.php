<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\StorefrontBrandCardData;
use Commerce\Catalog\Models\Brand;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Product\Models\Product;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

final class StorefrontBrandDirectory
{
    public const CACHE_KEY = 'storefront.brand-directory';

    public const CACHE_TTL_SECONDS = 300;

    /**
     * @return SupportCollection<int, StorefrontBrandCardData>
     */
    public function all(): SupportCollection
    {
        if (! class_exists(Brand::class) || ! Schema::hasTable('brands')) {
            return collect();
        }

        if (app()->runningUnitTests()) {
            return $this->build();
        }

        /** @var list<array<string, mixed>> $payload */
        $payload = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            return $this->build()->map(static fn (StorefrontBrandCardData $brand): array => [
                'name' => $brand->name,
                'slug' => $brand->slug,
                'url' => $brand->url,
                'letter' => $brand->letter,
                'productCount' => $brand->productCount,
                'logoUrl' => $brand->logoUrl,
                'monogram' => $brand->monogram,
            ])->all();
        });

        return collect($payload)->map(static fn (array $row): StorefrontBrandCardData => new StorefrontBrandCardData(
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            url: (string) $row['url'],
            letter: (string) $row['letter'],
            productCount: (int) $row['productCount'],
            logoUrl: isset($row['logoUrl']) && is_string($row['logoUrl']) ? $row['logoUrl'] : null,
            monogram: (string) $row['monogram'],
        ));
    }

    /**
     * @return SupportCollection<int, StorefrontBrandCardData>
     */
    public function forArchive(): SupportCollection
    {
        return $this->withProducts($this->all());
    }

    /**
     * @return SupportCollection<int, StorefrontBrandCardData>
     */
    public function popular(int $limit = 16): SupportCollection
    {
        return $this->withProducts($this->all())
            ->sortBy([
                ['productCount', 'desc'],
                ['name', 'asc'],
            ])
            ->values()
            ->take(max(1, $limit))
            ->values();
    }

    /**
     * @return list<string>
     */
    public function letters(SupportCollection $brands): array
    {
        return $brands
            ->pluck('letter')
            ->unique()
            ->sort(static function (string $left, string $right): int {
                if ($left === '#') {
                    return 1;
                }
                if ($right === '#') {
                    return -1;
                }

                return $left <=> $right;
            })
            ->values()
            ->all();
    }

    /**
     * @param  SupportCollection<int, StorefrontBrandCardData>  $brands
     * @return array<string, list<StorefrontBrandCardData>>
     */
    public function grouped(SupportCollection $brands): array
    {
        $groups = [];

        foreach ($this->letters($brands) as $letter) {
            $groups[$letter] = $brands
                ->filter(static fn (StorefrontBrandCardData $brand): bool => $brand->letter === $letter)
                ->values()
                ->all();
        }

        return $groups;
    }

    public static function letterAnchor(string $letter): string
    {
        $slug = Str::slug($letter);

        return $slug === '' ? 'other' : $slug;
    }

    public static function letterFor(string $name): string
    {
        $first = mb_strtoupper(mb_substr(trim($name), 0, 1));

        if ($first !== '' && preg_match('/^[A-Z]$/', $first) === 1) {
            return $first;
        }

        return '#';
    }

    public static function monogramFor(string $name): string
    {
        $first = mb_substr(trim($name), 0, 1);

        if ($first === '') {
            return '#';
        }

        if (preg_match('/^[A-Za-z]$/', $first) === 1) {
            return mb_strtoupper($first);
        }

        return $first;
    }

    /**
     * @param  SupportCollection<int, StorefrontBrandCardData>  $brands
     * @return SupportCollection<int, StorefrontBrandCardData>
     */
    private function withProducts(SupportCollection $brands): SupportCollection
    {
        return $brands
            ->filter(static fn (StorefrontBrandCardData $brand): bool => $brand->productCount > 0)
            ->values();
    }

    /**
     * @return SupportCollection<int, StorefrontBrandCardData>
     */
    private function build(): SupportCollection
    {
        try {
            $counts = $this->productCounts();
            $showRoute = Route::has('storefront.brands.show');

            return Brand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['uuid', 'name', 'slug', 'logo_media_uuid'])
                ->filter(static fn (Brand $brand): bool => filled($brand->slug))
                ->map(function (Brand $brand) use ($counts, $showRoute): StorefrontBrandCardData {
                    $name = (string) $brand->name;
                    $slug = (string) $brand->slug;

                    return new StorefrontBrandCardData(
                        name: $name,
                        slug: $slug,
                        url: $showRoute
                            ? route('storefront.brands.show', $slug)
                            : route('storefront.shop.index', ['brand' => $slug]),
                        letter: self::letterFor($name),
                        productCount: (int) $counts->get((string) $brand->uuid, 0),
                        logoUrl: $this->logoUrl($brand->logo_media_uuid),
                        monogram: self::monogramFor($name),
                    );
                })
                ->values();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * @return SupportCollection<string, int>
     */
    private function productCounts(): SupportCollection
    {
        if (! class_exists(Product::class) || ! Schema::hasTable('products')) {
            return collect();
        }

        return Product::query()
            ->visibleOnStorefront()
            ->whereNotNull('brand_uuid')
            ->select('brand_uuid')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('brand_uuid')
            ->pluck('aggregate', 'brand_uuid')
            ->map(static fn (mixed $count): int => (int) $count);
    }

    private function logoUrl(mixed $uuid): ?string
    {
        if (! is_string($uuid) || $uuid === '' || ! app()->bound(MediaQueryServiceInterface::class)) {
            return null;
        }

        try {
            $media = app(MediaQueryServiceInterface::class);

            return $media->getUrl($uuid, 'thumbnail')
                ?? $media->getUrl($uuid);
        } catch (Throwable) {
            return null;
        }
    }
}
