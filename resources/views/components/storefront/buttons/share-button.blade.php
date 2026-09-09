@props([
    'url',
    'title' => null,
])

@php
    $shareUrl = rawurlencode($url);
    $shareTitle = rawurlencode((string) $title);
    $shareMenuId = 'storefront-share-menu-'.substr(md5($url), 0, 8);
@endphp

<div {{ $attributes->class('storefront-share') }} data-share-root>
    <button
        type="button"
        class="storefront-share-btn"
        data-share-button
        data-share-url="{{ $url }}"
        data-share-title="{{ $title }}"
        aria-label="{{ __('storefront::storefront.share') }}"
        aria-expanded="false"
        aria-haspopup="menu"
        aria-controls="{{ $shareMenuId }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7" />
            <path d="M16 6l-4-4-4 4" />
            <path d="M12 2v14" />
        </svg>
        <span class="storefront-share-btn__label">{{ __('storefront::storefront.share') }}</span>
    </button>
    <div class="storefront-share__menu" id="{{ $shareMenuId }}" data-share-menu hidden role="menu">
        <a class="storefront-share__item" role="menuitem" href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer">
            {{ __('storefront::storefront.share_facebook') }}
        </a>
        <a class="storefront-share__item" role="menuitem" href="https://social-plugins.line.me/lineit/share?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer">
            {{ __('storefront::storefront.share_line') }}
        </a>
        <a class="storefront-share__item" role="menuitem" href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&amp;text={{ $shareTitle }}" target="_blank" rel="noopener noreferrer">
            {{ __('storefront::storefront.share_x') }}
        </a>
        <button type="button" class="storefront-share__item" role="menuitem" data-share-copy>
            {{ __('storefront::storefront.share_copy') }}
        </button>
    </div>
</div>
