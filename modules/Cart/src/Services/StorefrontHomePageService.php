<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Catalog\Models\Category;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Services\CmsStructuredDataBuilder;
use Commerce\Cms\Services\HomeContentQueryService;
use Commerce\Cms\Services\StorefrontBlogService;
use Commerce\Cms\Support\HomeContentCache;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Settings\SiteIdentityServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductImageResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class StorefrontHomePageService
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
        private readonly StorefrontNavigationCatalog $navigationCatalog,
        private readonly StorefrontInStockCatalog $inStockCatalog,
        private readonly StorefrontProductPageService $productPageService,
        private readonly ProductImageResolver $imageResolver,
        private readonly HomeContentQueryService $homeContent,
        private readonly StorefrontBlogService $blogService,
        private readonly CmsStructuredDataBuilder $structuredData,
        private readonly SiteIdentityServiceInterface $siteIdentity,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(?string $categorySlug = null): array
    {
        $commerce = $this->commerceContext();
        $products = $this->newArrivals($categorySlug);
        $this->imageResolver->preloadForProducts($products);
        $heroBanners = module_disabled('cms') ? [] : $this->homeContent->heroBanners();
        $latestPosts = $this->latestPosts();
        $pageSeo = $this->pageSeo($heroBanners);

        return [
            ...$commerce,
            'heroBanners' => $heroBanners,
            'promotionBanners' => module_disabled('cms') ? [] : $this->homeContent->promotionBanners(),
            'faqEntries' => module_disabled('cms') ? [] : $this->homeContent->faqEntries(),
            'homepageSections' => $this->visibleHomepageSections(),
            'arrivalCategories' => $this->arrivalCategories(),
            'activeArrivalCategory' => $categorySlug,
            'arrivalProducts' => $products,
            'stockLevels' => $this->productPageService->stockLevelsForCollection($products),
            'latestPosts' => $latestPosts,
            'blogService' => $this->blogService,
            'pageSeo' => $pageSeo,
            'structuredData' => $this->structuredData->homepage(
                $this->siteIdentity->name(),
                $pageSeo['canonical'],
                $pageSeo['description'],
                $this->siteIdentity->logoUrl('large') ?? $this->siteIdentity->logoUrl(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arrivalsPayload(?string $categorySlug = null): array
    {
        $commerce = $this->commerceContext();
        $products = $this->newArrivals($categorySlug);
        $this->imageResolver->preloadForProducts($products);

        return [
            ...$commerce,
            'arrivalProducts' => $products,
            'stockLevels' => $this->productPageService->stockLevelsForCollection($products),
        ];
    }

    /**
     * @return Collection<int, Category>
     */
    private function arrivalCategories(): Collection
    {
        return $this->navigationCatalog
            ->categories()
            ->filter(static fn (Category $category): bool => filled($category->slug))
            ->take(8)
            ->values();
    }

    /**
     * @return Collection<int, Product>
     */
    private function newArrivals(?string $categorySlug): Collection
    {
        $suffix = is_string($categorySlug) && $categorySlug !== '' ? $categorySlug : 'all';
        /** @var list<string> $uuids */
        $uuids = HomeContentCache::remember(
            'arrivals',
            fn (): array => $this->queryArrivalUuids($categorySlug),
            $suffix,
        );

        return $this->hydrateProducts($uuids);
    }

    /**
     * @return list<string>
     */
    private function queryArrivalUuids(?string $categorySlug): array
    {
        $query = Product::query()->visibleOnStorefront();
        $this->inStockCatalog->applyInStockVariantConstraint($query);

        if (is_string($categorySlug) && $categorySlug !== '') {
            $query->whereHas('categories', static function (Builder $categoryQuery) use ($categorySlug): void {
                $categoryQuery->where('slug', $categorySlug);
            });
        }

        return $query->latest()->limit(12)->pluck('uuid')->all();
    }

    /**
     * @param  list<string>  $uuids
     * @return Collection<int, Product>
     */
    private function hydrateProducts(array $uuids): Collection
    {
        if ($uuids === []) {
            return collect();
        }

        $order = array_flip($uuids);
        $query = Product::query()
            ->with(['variants', 'media', 'categories'])
            ->visibleOnStorefront()
            ->whereIn('uuid', $uuids);
        $this->inStockCatalog->applyInStockVariantConstraint($query);

        return $query->get()
            ->sortBy(static fn (Product $product): int => $order[$product->uuid] ?? 999)
            ->values();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function visibleHomepageSections(): array
    {
        $cmsDisabled = module_disabled('cms');
        $blogDisabled = module_disabled('blog');

        return array_values(array_filter(
            $this->homeContent->sections(),
            static function (array $section) use ($cmsDisabled, $blogDisabled): bool {
                $key = (string) ($section['key'] ?? '');

                if ($cmsDisabled && in_array($key, ['hero', 'promotions', 'faq'], true)) {
                    return false;
                }

                if ($blogDisabled && $key === 'articles') {
                    return false;
                }

                return true;
            },
        ));
    }

    /**
     * @return Collection<int, Post>
     */
    private function latestPosts(): Collection
    {
        if (module_disabled('blog')) {
            return collect();
        }
        $uuids = HomeContentCache::remember('articles', function (): array {
            return $this->blogService->publishedQuery()
                ->latest('published_at')
                ->limit(8)
                ->pluck('uuid')
                ->all();
        });

        if ($uuids === []) {
            return collect();
        }

        $order = array_flip($uuids);

        return $this->blogService->publishedQuery()
            ->with(['category', 'author'])
            ->whereIn('uuid', $uuids)
            ->get()
            ->sortBy(static fn (Post $post): int => $order[$post->uuid] ?? 999)
            ->values();
    }

    /**
     * @param  list<array{imageUrl?: string}>  $heroBanners
     * @return array{title: string, description: string, canonical: string, url: string, ogImage: ?string, ogType: string}
     */
    private function pageSeo(array $heroBanners): array
    {
        $canonical = route('storefront.home');
        $ogImage = $heroBanners[0]['imageUrl'] ?? $this->siteIdentity->logoUrl('large') ?? $this->siteIdentity->logoUrl();

        return [
            'title' => __('storefront::storefront.home'),
            'description' => __('storefront::storefront.home_seo_description'),
            'canonical' => $canonical,
            'url' => $canonical,
            'ogImage' => $ogImage,
            'ogType' => 'website',
        ];
    }

    /**
     * @return array{
     *     displayCurrency: string,
     *     baseCurrency: string,
     *     currencyConverter: CurrencyConverterInterface|null
     * }
     */
    private function commerceContext(): array
    {
        $cart = $this->cartService->get();
        $converter = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)
            : null;

        return [
            'displayCurrency' => $cart->currency,
            'baseCurrency' => $converter?->baseCurrency() ?? $cart->currency,
            'currencyConverter' => $converter,
        ];
    }
}
