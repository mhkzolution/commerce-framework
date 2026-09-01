<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\DTO\StorefrontShopFilters;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Commerce\Catalog\Support\CatalogMediaResolver;
use Commerce\Catalog\Support\CatalogSeoSync;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Product\Services\ProductImageResolver;
use Illuminate\Http\Request;

final class StorefrontShopPageBuilder
{
    public function __construct(
        private readonly StorefrontShopQueryService $shopQueryService,
        private readonly StorefrontShopFilterPresenter $shopFilterPresenter,
        private readonly CatalogMediaResolver $catalogMediaResolver,
        private readonly CatalogSeoSync $catalogSeo,
        private readonly CartServiceInterface $cartService,
        private readonly StorefrontNavigationCatalog $navigationCatalog,
        private readonly StorefrontShopBreadcrumbBuilder $breadcrumbBuilder,
        private readonly ProductImageResolver $imageResolver,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        Request $request,
        ?Collection $collection = null,
        ?Category $category = null,
        ?Brand $brand = null,
    ): array {
        if ($collection !== null) {
            $request->merge(['collection' => $collection->slug]);
        }

        if ($category !== null) {
            $request->merge(['category' => $category->slug]);
        }

        if ($brand !== null) {
            $request->merge(['brand' => $brand->slug]);
        }

        $filters = StorefrontShopFilters::fromRequest($request);
        $filterableAttributes = Attribute::query()
            ->where('is_filterable', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get();
        $filterCatalog = $this->shopFilterPresenter->build($filterableAttributes);
        $products = $this->shopQueryService->paginate($filters, $filterCatalog);
        $this->imageResolver->preloadForProducts($products);
        $cart = $this->cartService->get();
        $converter = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)
            : null;

        $categories = $this->navigationCatalog->categories();
        $activeCollection = $collection ?? (
            $filters->collection !== null
                ? Collection::query()->where('slug', $filters->collection)->first()
                : null
        );
        $activeCategory = $category ?? (
            $filters->category !== null
                ? Category::query()->where('slug', $filters->category)->first()
                : null
        );
        $activeBrand = $brand ?? (
            $filters->brand !== null
                ? Brand::query()
                    ->where('slug', $filters->brand)
                    ->orWhere('uuid', $filters->brand)
                    ->first()
                : null
        );
        $availableBrandSlugs = $filterCatalog['brandSlugs'] ?? [];
        $brands = Brand::query()
            ->where('is_active', true)
            ->when($availableBrandSlugs !== [], fn ($query) => $query->whereIn('slug', $availableBrandSlugs))
            ->when($availableBrandSlugs === [], fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('name')
            ->get();
        $this->catalogMediaResolver->preloadNavigationMedia($categories, $brands);
        $filterCategories = $categories;

        $landing = $this->resolveLanding($activeCollection, $activeCategory, $activeBrand);
        $breadcrumbItems = $this->breadcrumbBuilder->build(
            $filters,
            $activeCategory,
            $activeCollection,
            $activeBrand,
            $landing !== null,
        );
        $pageSeo = $landing !== null
            ? $this->catalogSeo->pageMeta(
                $landing['seoEntityType'],
                $landing['seoEntityUuid'],
                $landing['title'],
                $landing['description'],
            )
            : null;

        return [
            'filters' => $filters,
            'filterCatalog' => $filterCatalog,
            'products' => $products,
            'stockLevels' => $this->shopQueryService->stockLevelsForProducts($products),
            'categories' => $categories,
            'collections' => $this->navigationCatalog->collections(),
            'activeCollection' => $activeCollection,
            'activeCategory' => $activeCategory,
            'activeBrand' => $activeBrand,
            'brands' => $brands,
            'filterCategories' => $filterCategories,
            'categoryImageUrls' => $this->catalogMediaResolver->categoryImageUrls($categories),
            'brandLogoUrls' => $this->catalogMediaResolver->brandLogoUrls($brands),
            'collectionCoverUrl' => $activeCollection?->cover_media_uuid
                ? $this->catalogMediaResolver->url($activeCollection->cover_media_uuid)
                : null,
            'landing' => $landing,
            'breadcrumbItems' => $breadcrumbItems,
            'pageSeo' => $pageSeo,
            'displayCurrency' => $cart->currency,
            'baseCurrency' => $converter?->baseCurrency() ?? $cart->currency,
            'currencyConverter' => $converter,
        ];
    }

    /**
     * @return array{eyebrow: string, title: string, description: ?string, coverUrl: ?string, coverVariant: string, shopUrl: string, seoEntityType: string, seoEntityUuid: string}|null
     */
    private function resolveLanding(?Collection $collection, ?Category $category, ?Brand $brand): ?array
    {
        if ($collection !== null) {
            return [
                'eyebrow' => (string) __('storefront::storefront.filter_collection'),
                'title' => $collection->name,
                'description' => $collection->description,
                'coverUrl' => $collection->cover_media_uuid
                    ? $this->catalogMediaResolver->url($collection->cover_media_uuid)
                    : null,
                'coverVariant' => 'cover',
                'shopUrl' => route('storefront.catalog.collections.show', $collection->slug),
                'seoEntityType' => Collection::SEO_ENTITY_TYPE,
                'seoEntityUuid' => $collection->uuid,
            ];
        }

        if ($category !== null) {
            return [
                'eyebrow' => (string) __('storefront::storefront.filter_category'),
                'title' => $category->name,
                'description' => $category->description,
                'coverUrl' => $category->image_media_uuid
                    ? $this->catalogMediaResolver->url($category->image_media_uuid)
                    : null,
                'coverVariant' => 'cover',
                'shopUrl' => route('storefront.catalog.categories.show', $category->slug),
                'seoEntityType' => Category::SEO_ENTITY_TYPE,
                'seoEntityUuid' => $category->uuid,
            ];
        }

        if ($brand !== null) {
            return [
                'eyebrow' => (string) __('storefront::storefront.filter_brand'),
                'title' => $brand->name,
                'description' => $brand->description,
                'coverUrl' => $brand->logo_media_uuid
                    ? $this->catalogMediaResolver->url($brand->logo_media_uuid)
                    : null,
                'coverVariant' => 'logo',
                'shopUrl' => route('storefront.catalog.brands.show', $brand->slug),
                'seoEntityType' => Brand::SEO_ENTITY_TYPE,
                'seoEntityUuid' => $brand->uuid,
            ];
        }

        return null;
    }
}
