@if ($arrivalProducts->isNotEmpty())
    <section class="storefront-home__section storefront-home-arrivals" aria-labelledby="home-arrivals-title" data-home-reveal>
        <div class="storefront-home__inner">
            <header class="storefront-home-section-header">
                <div>
                    <h2 id="home-arrivals-title" class="storefront-home-section-header__title">{{ __('storefront::storefront.home_new_arrivals') }}</h2>
                    <p class="storefront-home-section-header__subtitle">{{ __('storefront::storefront.home_new_arrivals_subtitle') }}</p>
                </div>
            </header>

            <div class="storefront-home-arrivals__toolbar">
                <div class="storefront-home-arrivals__tabs" role="tablist" aria-label="{{ __('storefront::storefront.filter_category') }}" data-arrival-tabs>
                    <button
                        type="button"
                        class="storefront-home-arrivals__tab {{ $activeArrivalCategory === null ? 'is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ $activeArrivalCategory === null ? 'true' : 'false' }}"
                        data-arrival-tab
                        data-category=""
                    >
                        {{ __('storefront::storefront.filter_all') }}
                    </button>
                    @foreach ($arrivalCategories as $category)
                        <button
                            type="button"
                            class="storefront-home-arrivals__tab {{ $activeArrivalCategory === $category->slug ? 'is-active' : '' }}"
                            role="tab"
                            aria-selected="{{ $activeArrivalCategory === $category->slug ? 'true' : 'false' }}"
                            data-arrival-tab
                            data-category="{{ $category->slug }}"
                        >
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>

                <a href="{{ route('storefront.shop.index') }}" class="storefront-home-section-header__action">
                    {{ __('storefront::storefront.home_view_all') }}
                </a>
            </div>

            <div class="storefront-home-arrivals__slider-wrap" data-arrivals-root>
                <div class="storefront-home-arrivals__skeletons" data-arrivals-skeletons hidden>
                    @for ($i = 0; $i < 5; $i++)
                        <div class="storefront-home-skeleton-card" aria-hidden="true">
                            <div class="storefront-home-skeleton-card__media"></div>
                            <div class="storefront-home-skeleton-card__line"></div>
                            <div class="storefront-home-skeleton-card__line storefront-home-skeleton-card__line--short"></div>
                        </div>
                    @endfor
                </div>

                <x-storefront.navigation.slider
                    class="storefront-home-arrivals__slider"
                    :lazy="true"
                    :label="__('storefront::storefront.home_new_arrivals')"
                    data-arrivals-slider
                >
                    @include('cart::storefront.partials.home-product-slides')
                </x-storefront.navigation.slider>
            </div>
        </div>
    </section>
@endif
