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

    <x-slot:footer>
        <x-storefront.buttons.primary-button
            :href="auth('customer')->check() ? route('storefront.account.wishlist') : route('storefront.wishlist')"
            class="w-full justify-center"
        >
            {{ __('storefront::storefront.view_wishlist') }}
        </x-storefront.buttons.primary-button>
    </x-slot:footer>
</x-storefront.navigation.drawer>

<div
    hidden
    data-wishlist-root
    data-authenticated="{{ auth('customer')->check() ? '1' : '0' }}"
    data-storage-key="{{ config('wishlist.local_storage_key', 'commerce:wishlist') }}"
    data-wishlist-index-url="{{ route('api.v1.storefront.wishlist.index') }}"
    data-wishlist-store-url="{{ route('api.v1.storefront.wishlist.items.store') }}"
    data-wishlist-destroy-url="{{ route('api.v1.storefront.wishlist.items.destroy') }}"
    data-wishlist-merge-url="{{ route('api.v1.storefront.wishlist.merge') }}"
    data-wishlist-preview-url="{{ route('api.v1.storefront.wishlist.preview') }}"
></div>
