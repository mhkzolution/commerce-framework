<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\DTO\StorefrontShopFilters;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;

final class StorefrontShopBreadcrumbBuilder
{
    /**
     * @return list<array{label: string, url?: string}>
     */
    public function build(
        StorefrontShopFilters $filters,
        ?Category $category,
        ?Collection $collection,
        ?Brand $brand,
        bool $isCatalogLanding = false,
    ): array {
        if ($isCatalogLanding) {
            return $this->catalogLandingTrail($category, $collection, $brand);
        }

        if (! $this->shouldShowOnShop($filters, $category, $collection, $brand)) {
            return [];
        }

        return $this->shopTrail($filters, $category, $collection, $brand);
    }

    /**
     * @return list<array{label: string, url?: string}>
     */
    private function catalogLandingTrail(?Category $category, ?Collection $collection, ?Brand $brand): array
    {
        $entity = $category ?? $collection ?? $brand;
        if ($entity === null) {
            return [];
        }

        return [
            ['label' => (string) __('storefront::storefront.shop'), 'url' => route('storefront.shop.index')],
            ['label' => (string) $entity->name],
        ];
    }

    /**
     * @return list<array{label: string, url?: string}>
     */
    private function shopTrail(
        StorefrontShopFilters $filters,
        ?Category $category,
        ?Collection $collection,
        ?Brand $brand,
    ): array {
        $items = [
            ['label' => (string) __('storefront::storefront.shop'), 'url' => route('storefront.shop.index')],
        ];

        if ($category !== null) {
            $items[] = $this->hasTrailingContext($filters)
                ? ['label' => $category->name, 'url' => route('storefront.catalog.categories.show', $category->slug)]
                : ['label' => $category->name];
        } elseif ($collection !== null) {
            $items[] = $this->hasTrailingContext($filters)
                ? ['label' => $collection->name, 'url' => route('storefront.catalog.collections.show', $collection->slug)]
                : ['label' => $collection->name];
        } elseif ($brand !== null) {
            $items[] = $this->hasTrailingContext($filters)
                ? ['label' => $brand->name, 'url' => route('storefront.catalog.brands.show', $brand->slug)]
                : ['label' => $brand->name];
        }

        if ($filters->search !== null && $filters->search !== '') {
            $items[] = [
                'label' => (string) __('storefront::storefront.search_results_for', ['query' => $filters->search]),
            ];
        } elseif ($this->hasSecondaryFilters($filters)) {
            $items[] = ['label' => (string) __('storefront::storefront.filtered_results')];
        }

        return $items;
    }

    private function shouldShowOnShop(
        StorefrontShopFilters $filters,
        ?Category $category,
        ?Collection $collection,
        ?Brand $brand,
    ): bool {
        return $category !== null
            || $collection !== null
            || $brand !== null
            || ($filters->search !== null && $filters->search !== '')
            || $this->hasSecondaryFilters($filters);
    }

    private function hasTrailingContext(StorefrontShopFilters $filters): bool
    {
        return ($filters->search !== null && $filters->search !== '')
            || $this->hasSecondaryFilters($filters);
    }

    private function hasSecondaryFilters(StorefrontShopFilters $filters): bool
    {
        return $filters->priceMin !== null
            || $filters->priceMax !== null
            || $filters->size !== null
            || $filters->color !== null
            || $filters->age !== null
            || $filters->gender !== null;
    }
}
