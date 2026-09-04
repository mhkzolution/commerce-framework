@props([
    'url',
    'title' => null,
])

<button
    type="button"
    {{ $attributes->class('storefront-share-btn') }}
    data-share-button
    data-share-url="{{ $url }}"
    data-share-title="{{ $title }}"
    aria-label="{{ __('storefront::storefront.share') }}"
>
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
        <path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7" />
        <path d="M16 6l-4-4-4 4" />
        <path d="M12 2v14" />
    </svg>
    <span class="storefront-share-btn__label">{{ __('storefront::storefront.share') }}</span>
</button>
