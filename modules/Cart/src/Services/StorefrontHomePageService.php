<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\DTO\HomepageBrandingData;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Services\HomeContentQueryService;
use Commerce\Cms\Services\StorefrontBlogService;
use Commerce\Cms\Support\HomeContentCache;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

final class StorefrontHomePageService
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
        private readonly HomeContentQueryService $homeContent,
        private readonly HomepageNavigationQuery $navigation,
        private readonly HomepageProductQuery $products,
        private readonly HomepageBrandingQuery $branding,
        private readonly MediaQueryServiceInterface $media,
        private readonly StorefrontBlogService $blogService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(?string $categorySlug = null): array
    {
        $commerce = $this->commerceContext();
        $cards = $this->products->arrivals($categorySlug);
        $heroBanners = module_disabled('cms') ? [] : $this->homeContent->heroBanners();
        $branding = $this->branding->current();
        $latestPosts = $this->latestPosts();
        $pageSeo = $this->pageSeo($heroBanners, $branding);

        return [
            ...$commerce,
            'heroBanners' => $heroBanners,
            'promotionBanners' => module_disabled('cms') ? [] : $this->homeContent->promotionBanners(),
            'faqEntries' => module_disabled('cms') ? [] : $this->homeContent->faqEntries(),
            'homepageSections' => $this->visibleHomepageSections(),
            'arrivalCategories' => $this->navigation->arrivalTabs(),
            'activeArrivalCategory' => $categorySlug,
            'arrivalProducts' => $cards,
            'latestPosts' => $latestPosts,
            'blogService' => $this->blogService,
            'branding' => $branding,
            'pageSeo' => $pageSeo,
            'structuredData' => $this->structuredData($branding, $pageSeo),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arrivalsPayload(?string $categorySlug = null): array
    {
        return [
            ...$this->commerceContext(),
            'arrivalProducts' => $this->products->arrivals($categorySlug),
        ];
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
    private function pageSeo(array $heroBanners, HomepageBrandingData $branding): array
    {
        $canonical = $this->canonicalUrl();
        $ogImage = $heroBanners[0]['imageUrl'] ?? $branding->logoUrl;

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
     * @param  array{canonical: string, description: string, ogImage: ?string}  $pageSeo
     * @return array<string, mixed>
     */
    private function structuredData(HomepageBrandingData $branding, array $pageSeo): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $branding->name,
            'url' => $pageSeo['canonical'],
            'description' => $pageSeo['description'],
        ];

        $image = $pageSeo['ogImage'] ?? $branding->logoUrl;
        if (is_string($image) && $image !== '') {
            $data['image'] = $image;
        }

        return $data;
    }

    private function canonicalUrl(): string
    {
        return Route::has('storefront.home')
            ? route('storefront.home')
            : url('/');
    }

    private function mediaUrl(?string $uuid): ?string
    {
        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        return $this->media->getUrl($uuid, 'large')
            ?? $this->media->getUrl($uuid, 'medium')
            ?? $this->media->getUrl($uuid);
    }

    /**
     * @return array{
     *     displayCurrency: string,
     *     baseCurrency: string,
     *     currencyConverter: CurrencyConverterInterface|null
     * }
     */
    public function commerceContext(): array
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
