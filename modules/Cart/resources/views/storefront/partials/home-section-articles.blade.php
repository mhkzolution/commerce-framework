@if (! module_disabled('blog') && $latestPosts->isNotEmpty())
    <section class="storefront-home__section storefront-home-articles" aria-labelledby="home-articles-title" data-home-reveal>
        <x-storefront.layout.page-container class="storefront-home__inner">
            <header class="storefront-home-section-header storefront-home-section-header--split">
                <div>
                    <h2 id="home-articles-title" class="storefront-home-section-header__title">{{ __('storefront::storefront.home_latest_articles') }}</h2>
                    <p class="storefront-home-section-header__subtitle">{{ __('storefront::storefront.home_latest_articles_subtitle') }}</p>
                </div>
                <a href="{{ route('storefront.cms.posts.index') }}" class="storefront-home-section-header__action">
                    {{ __('storefront::storefront.home_view_all') }}
                </a>
            </header>

            <x-storefront.navigation.slider
                class="storefront-home-articles__slider"
                :loop="true"
                :lazy="true"
                :label="__('storefront::storefront.home_latest_articles')"
            >
                @foreach ($latestPosts as $post)
                    <div class="storefront-slider__slide storefront-home-articles__slide">
                        <x-storefront.cards.blog-card :post="$post" :blog-service="$blogService" />
                    </div>
                @endforeach
            </x-storefront.navigation.slider>
        </x-storefront.layout.page-container>
    </section>
@endif
