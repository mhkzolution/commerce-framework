@props([
    'authenticated' => false,
])

@php
    $previewUrl = Route::has('api.v1.storefront.wishlist.preview') ? route('api.v1.storefront.wishlist.preview') : '';
    $indexUrl = Route::has('api.v1.storefront.wishlist.index') ? route('api.v1.storefront.wishlist.index') : '';
    $storeUrl = Route::has('api.v1.storefront.wishlist.items.store') ? route('api.v1.storefront.wishlist.items.store') : '';
    $destroyUrl = Route::has('api.v1.storefront.wishlist.items.destroy') ? route('api.v1.storefront.wishlist.items.destroy') : '';
    $mergeUrl = Route::has('api.v1.storefront.wishlist.merge') ? route('api.v1.storefront.wishlist.merge') : '';
@endphp

<x-storefront.navigation.drawer id="wishlist" :label="__('storefront::storefront.wishlist')">
    <x-slot:header>
        <h2 class="storefront-drawer__title">{{ __('storefront::storefront.wishlist') }}</h2>
        <button type="button" class="storefront-drawer__close" data-drawer-close="wishlist" data-drawer-close-trigger aria-label="{{ __('storefront::storefront.close') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>
    </x-slot:header>

    <p class="storefront-drawer__empty" data-wishlist-empty>{{ __('storefront::storefront.wishlist_empty') }}</p>
    <p class="storefront-drawer__loading" data-wishlist-loading hidden>{{ __('storefront::storefront.loading') }}</p>

    <div
        class="storefront-wishlist-drawer__list"
        data-wishlist-list
        data-remove-label="{{ __('storefront::storefront.remove') }}"
        data-no-image-label="{{ __('storefront::storefront.no_image') }}"
    ></div>
</x-storefront.navigation.drawer>

<div
    hidden
    data-wishlist-root
    data-authenticated="{{ $authenticated ? '1' : '0' }}"
    data-storage-key="{{ config('wishlist.local_storage_key', 'commerce:wishlist') }}"
    data-wishlist-index-url="{{ $indexUrl }}"
    data-wishlist-store-url="{{ $storeUrl }}"
    data-wishlist-destroy-url="{{ $destroyUrl }}"
    data-wishlist-merge-url="{{ $mergeUrl }}"
    data-wishlist-preview-url="{{ $previewUrl }}"
></div>
