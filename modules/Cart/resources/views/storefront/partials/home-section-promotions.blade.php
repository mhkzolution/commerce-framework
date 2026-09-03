@php
    $layout = $section['layout'] ?? 'slider';
    $columns = max(1, min(4, (int) ($section['settings']['columns'] ?? 2)));
@endphp

@if ($promotionBanners !== [])
    <section
        class="storefront-home__section storefront-home-promos storefront-home-promos--{{ str_replace('_', '-', $layout) }}"
        aria-label="{{ __('storefront::storefront.home_promotions') }}"
        data-home-reveal
    >
        <div class="storefront-home__inner">
            @if ($layout === 'slider')
                <x-storefront.navigation.slider
                    class="storefront-home-promos__slider"
                    :autoplay="$section['settings']['autoplay'] ?? true"
                    :loop="true"
                    :lazy="true"
                    :label="__('storefront::storefront.home_promotions')"
                >
                    @foreach ($promotionBanners as $banner)
                        <div class="storefront-slider__slide storefront-home-promos__slide">
                            @include('cart::storefront.partials.home-promo-banner', ['banner' => $banner])
                        </div>
                    @endforeach
                </x-storefront.navigation.slider>
            @else
                <div class="storefront-home-promos__layout storefront-home-promos__layout--{{ str_replace('_', '-', $layout) }}" style="--home-promo-columns: {{ $columns }}">
                    @foreach ($promotionBanners as $banner)
                        <div class="storefront-home-promos__item">
                            @include('cart::storefront.partials.home-promo-banner', ['banner' => $banner])
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
