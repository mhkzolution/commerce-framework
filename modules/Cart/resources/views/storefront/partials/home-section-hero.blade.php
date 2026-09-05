@if ($heroBanners !== [])
    <section class="storefront-home-hero" aria-label="{{ __('storefront::storefront.home_hero') }}">
        <x-storefront.navigation.slider
            class="storefront-home-hero__slider"
            :autoplay="$section['settings']['autoplay'] ?? true"
            :loop="true"
            :label="__('storefront::storefront.home_hero')"
        >
            @foreach ($heroBanners as $index => $banner)
                <div class="storefront-slider__slide storefront-home-hero__slide">
                    @if (($banner['type'] ?? 'image') === 'video' && ! empty($banner['videoUrl']))
                        @if (! empty($banner['mobileVideoUrl']))
                            <video
                                class="storefront-home-hero__video storefront-home-hero__video--mobile"
                                autoplay
                                muted
                                loop
                                playsinline
                                poster="{{ $banner['mobileImageUrl'] ?? $banner['imageUrl'] }}"
                                @if ($index !== 0) preload="none" @endif
                            >
                                <source src="{{ $banner['mobileVideoUrl'] }}" type="video/mp4">
                            </video>
                        @endif
                        <video
                            class="storefront-home-hero__video @if (! empty($banner['mobileVideoUrl'])) storefront-home-hero__video--desktop @endif"
                            autoplay
                            muted
                            loop
                            playsinline
                            poster="{{ $banner['imageUrl'] }}"
                            @if ($index !== 0) preload="none" @endif
                        >
                            <source src="{{ $banner['videoUrl'] }}" type="video/mp4">
                        </video>
                    @else
                        <picture>
                            @if (! empty($banner['mobileImageUrl']))
                                <source
                                    media="(max-width: 767px)"
                                    srcset="{{ $banner['mobileImageSrcset'] ?? $banner['mobileImageUrl'] }}"
                                >
                            @endif
                            <img
                                src="{{ $banner['imageUrl'] }}"
                                @if (! empty($banner['imageSrcset'])) srcset="{{ $banner['imageSrcset'] }}" @endif
                                sizes="{{ config('media.sizes.hero') }}"
                                alt=""
                                class="storefront-home-hero__image"
                                width="1920"
                                height="560"
                                @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                                decoding="async"
                            >
                        </picture>
                    @endif
                </div>
            @endforeach
        </x-storefront.navigation.slider>
    </section>
@endif
