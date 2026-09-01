<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Cart\Support\StorefrontMoney;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Search\SearchQueryInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductImageResolver;
use Commerce\Product\Services\ProductSearchIndexer;

final class StorefrontSearchAutocompleteService
{
    public function __construct(
        private readonly SearchQueryInterface $searchQuery,
        private readonly StorefrontInStockCatalog $inStockCatalog,
        private readonly StorefrontNavigationCatalog $navigationCatalog,
        private readonly ProductImageResolver $imageResolver,
    ) {}

    /**
     * @return array{
     *     products: list<array<string, mixed>>,
     *     categories: list<array<string, mixed>>,
     *     collections: list<array<string, mixed>>,
     *     brands: list<array<string, mixed>>,
     *     shop_url: string,
     * }
     */
    public function suggest(string $query, ?string $categorySlug = null, int $limit = 8): array
    {
        $query = trim($query);
        $limit = max(1, min(12, $limit));
        $productLimit = min(5, $limit);
        $catalogLimit = min(3, max(1, $limit - $productLimit));

        if ($query === '') {
            return $this->emptyPayload();
        }

        $needle = mb_strtolower($query);
        $currency = $this->displayCurrency();

        return [
            'products' => $this->productSuggestions($query, $categorySlug, $productLimit, $currency),
            'categories' => $this->catalogSuggestions(
                $this->navigationCatalog->categories(),
                $needle,
                'storefront.catalog.categories.show',
                $catalogLimit,
            ),
            'collections' => $this->catalogSuggestions(
                $this->navigationCatalog->collections(),
                $needle,
                'storefront.catalog.collections.show',
                $catalogLimit,
            ),
            'brands' => $this->catalogSuggestions(
                $this->navigationCatalog->brands(),
                $needle,
                'storefront.catalog.brands.show',
                $catalogLimit,
            ),
            'shop_url' => route('storefront.shop.index', array_filter([
                'search' => $query,
                'category' => $categorySlug,
            ])),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productSuggestions(string $query, ?string $categorySlug, int $limit, string $currency): array
    {
        $result = $this->searchQuery->search(
            ProductSearchIndexer::INDEX,
            $query,
            ['status' => 'published'],
            1,
            max($limit * 4, 20),
        );

        $uuids = array_values(array_filter(array_map(
            static fn (array $hit): ?string => isset($hit['uuid']) ? (string) $hit['uuid'] : null,
            $result->getHits(),
        )));

        if ($uuids === []) {
            return [];
        }

        $inStockIds = $this->inStockCatalog->productIds();

        $products = Product::query()
            ->with(['variants', 'media'])
            ->visibleOnStorefront()
            ->whereIn('uuid', $uuids)
            ->when($inStockIds !== [], fn ($builder) => $builder->whereIn('id', $inStockIds))
            ->when($inStockIds === [], fn ($builder) => $builder->whereRaw('1 = 0'))
            ->when($categorySlug !== null, function ($builder) use ($categorySlug): void {
                $builder->whereHas('categories', static function ($categoryQuery) use ($categorySlug): void {
                    $categoryQuery->where('slug', $categorySlug);
                });
            })
            ->get()
            ->sortBy(static function (Product $product) use ($uuids): int {
                $index = array_search($product->uuid, $uuids, true);

                return $index === false ? PHP_INT_MAX : (int) $index;
            })
            ->take($limit)
            ->values();

        $this->imageResolver->preloadForProducts($products);

        return $products->map(function (Product $product) use ($currency): array {
            $variant = $product->defaultVariant();
            $price = (float) ($variant?->price ?? 0);
            $displayPrice = $price;

            if (app()->bound(CurrencyConverterInterface::class)) {
                $converter = app(CurrencyConverterInterface::class);
                $baseCurrency = $converter->baseCurrency();
                if ($currency !== $baseCurrency) {
                    $displayPrice = $converter->convert($price, $baseCurrency, $currency);
                }
            }

            return [
                'uuid' => $product->uuid,
                'name' => $product->name,
                'slug' => $product->slug,
                'url' => route('storefront.products.show', $product->slug),
                'image_url' => $this->imageResolver->urlsForProduct($product, 'thumbnail', 1)[0] ?? null,
                'price' => $displayPrice,
                'currency' => $currency,
                'price_label' => StorefrontMoney::formatMajor($displayPrice, $currency, 0),
            ];
        })->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Category|Collection|Brand>  $items
     * @return list<array<string, mixed>>
     */
    private function catalogSuggestions(
        \Illuminate\Support\Collection $items,
        string $needle,
        string $routeName,
        int $limit,
    ): array {
        return $items
            ->filter(static fn ($item) => filled($item->slug))
            ->filter(static fn ($item) => str_contains(mb_strtolower((string) $item->name), $needle))
            ->take($limit)
            ->map(static fn ($item) => [
                'name' => (string) $item->name,
                'slug' => (string) $item->slug,
                'url' => route($routeName, $item->slug),
            ])
            ->values()
            ->all();
    }

    private function displayCurrency(): string
    {
        if (app()->bound(CartStorageInterface::class)) {
            return app(CartStorageInterface::class)->currency();
        }

        return (string) config('cart.default_currency', 'THB');
    }

    /**
     * @return array{
     *     products: list<array<string, mixed>>,
     *     categories: list<array<string, mixed>>,
     *     collections: list<array<string, mixed>>,
     *     brands: list<array<string, mixed>>,
     *     shop_url: string,
     * }
     */
    private function emptyPayload(): array
    {
        return [
            'products' => [],
            'categories' => [],
            'collections' => [],
            'brands' => [],
            'shop_url' => route('storefront.shop.index'),
        ];
    }
}
