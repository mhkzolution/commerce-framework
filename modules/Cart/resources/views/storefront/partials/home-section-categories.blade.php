@if (($featuredCategories ?? []) !== [])
    <section class="storefront-home__section storefront-home-categories" aria-labelledby="home-categories-title" data-home-reveal>
        <x-storefront.layout.page-container class="storefront-home__inner">
            <header class="storefront-home-section-header">
                <div>
                    <h2 id="home-categories-title" class="storefront-home-section-header__title">{{ __('storefront::storefront.home_featured_categories') }}</h2>
                    <p class="storefront-home-section-header__subtitle">{{ __('storefront::storefront.home_featured_categories_subtitle') }}</p>
                </div>
            </header>

            <div class="storefront-home-categories__track">
                @foreach ($featuredCategories as $category)
                    <a href="{{ $category->url ?? route('storefront.shop.index') }}" class="storefront-home-categories__card">
                        <span class="storefront-home-categories__media">
                            @if ($category->imageUrl)
                                <x-storefront.media.img
                                    :src="$category->imageUrl"
                                    :srcset="$category->imageSrcset"
                                    :sizes="config('media.sizes.category')"
                                    alt=""
                                    class="storefront-home-categories__image"
                                    width="320"
                                    height="320"
                                />
                            @else
                                <span class="storefront-home-categories__placeholder" aria-hidden="true">{{ mb_substr($category->name, 0, 1) }}</span>
                            @endif
                        </span>
                        <span class="storefront-home-categories__name">{{ $category->name }}</span>
                        @if ($category->productCount !== null && $category->productCount > 0)
                            <span class="storefront-home-categories__count">{{ trans_choice('storefront::storefront.shop_results', $category->productCount, ['count' => $category->productCount]) }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </x-storefront.layout.page-container>
    </section>
@endif
