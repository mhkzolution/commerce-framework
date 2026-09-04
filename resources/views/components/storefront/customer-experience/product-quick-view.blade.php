@props(['config' => []])

@php
    $quickViewI18n = [
        'addToCart' => __('storefront::storefront.add_to_cart'),
        'buyNow' => __('storefront::storefront.buy_now'),
        'inStock' => __('storefront::storefront.in_stock'),
        'outOfStock' => __('storefront::storefront.out_of_stock'),
        'viewFullDetails' => __('storefront::storefront.view_full_details'),
        'remaining' => __('storefront::storefront.remaining_stock'),
        'quantity' => __('storefront::storefront.quantity'),
        'decrease' => __('storefront::storefront.decrease_quantity'),
        'increase' => __('storefront::storefront.increase_quantity'),
        'unavailable' => __('storefront::storefront.unavailable'),
    ];
@endphp

<div
    class="cx-quick-view"
    data-quick-view
    data-quick-view-config='@json($config)'
    data-quick-view-url="{{ url('/api/v1/storefront/products') }}"
    data-cart-url="{{ route('storefront.cart.items.store') }}"
    data-i18n='@json($quickViewI18n)'
    hidden
>
    <div class="cx-quick-view__backdrop" data-quick-view-close></div>

    <aside
        class="cx-quick-view__panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cx-quick-view-title"
        data-quick-view-panel
    >
        <button type="button" class="cx-quick-view__close" data-quick-view-close aria-label="{{ __('storefront::storefront.close') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>

        <div class="cx-quick-view__scroll" data-quick-view-body>
            <div class="cx-quick-view__loading">{{ __('storefront::storefront.scroll_to_load') }}</div>
        </div>

        <div class="cx-quick-view__sticky" data-quick-view-sticky></div>
    </aside>
</div>
