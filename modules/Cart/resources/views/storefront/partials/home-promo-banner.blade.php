@if (! empty($banner['url']))
    <a
        href="{{ $banner['url'] }}"
        class="storefront-home-promos__link"
        @if ($banner['openInNewTab']) target="_blank" rel="noopener noreferrer" @endif
    >
        <img
            src="{{ $banner['imageUrl'] }}"
            alt="{{ $banner['title'] }}"
            class="storefront-home-promos__image"
            width="1240"
            height="420"
            loading="lazy"
            decoding="async"
        >
    </a>
@else
    <img
        src="{{ $banner['imageUrl'] }}"
        alt="{{ $banner['title'] }}"
        class="storefront-home-promos__image"
        width="1240"
        height="420"
        loading="lazy"
        decoding="async"
    >
@endif
