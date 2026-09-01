<x-storefront.product-grid data-shop-grid>
    @include('cart::storefront.partials.product-grid-items')
</x-storefront.product-grid>

@if ($products->hasMorePages())
    <div
        class="storefront-shop__pagination"
        data-shop-pagination
        data-next-url="{{ $products->appends(request()->except('page'))->nextPageUrl() }}"
    >
        <button type="button" class="storefront-shop__load-more-btn" data-shop-load-more-btn>
            <span data-shop-load-more-label>{{ __('storefront::storefront.load_more') }}</span>
            <span class="storefront-shop__loading storefront-shop__loading--desktop" data-shop-loading hidden>{{ __('storefront::storefront.loading_products') }}</span>
        </button>
        <div class="storefront-shop__sentinel" data-shop-load-more-sentinel>
            <div class="storefront-shop__infinite-loading" data-shop-infinite-loading aria-live="polite" aria-busy="false">
                <span class="storefront-shop__infinite-loading-spinner" aria-hidden="true"></span>
                <span>{{ __('storefront::storefront.loading_products') }}</span>
            </div>
        </div>
    </div>
@endif
