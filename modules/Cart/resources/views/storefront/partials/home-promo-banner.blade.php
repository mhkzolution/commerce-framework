@if (! empty($banner['url']))
    <a
        href="{{ $banner['url'] }}"
        class="storefront-home-promos__link"
        @if ($banner['openInNewTab']) target="_blank" rel="noopener noreferrer" @endif
    >
        <x-storefront.media.img
            :src="$banner['imageUrl']"
            :srcset="$banner['imageSrcset'] ?? null"
            :sizes="config('media.sizes.banner')"
            :alt="$banner['title']"
            class="storefront-home-promos__image"
            width="1240"
            height="420"
        />
    </a>
@else
    <x-storefront.media.img
        :src="$banner['imageUrl']"
        :srcset="$banner['imageSrcset'] ?? null"
        :sizes="config('media.sizes.banner')"
        :alt="$banner['title']"
        class="storefront-home-promos__image"
        width="1240"
        height="420"
    />
@endif
