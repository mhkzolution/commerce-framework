@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.wishlist'))

@section('content')
    <x-storefront.account.layout
        :customer="$customer"
        active="wishlist"
        :title="__('storefront::storefront.wishlist')"
        :description="__('storefront::storefront.wishlist_page_description')"
    >
        @if ($wishlistItems === [])
            <x-storefront.empty-state
                :title="__('storefront::storefront.wishlist_empty')"
                :description="__('storefront::storefront.wishlist_empty_description')"
            >
                <x-storefront.buttons.primary-button :href="route('storefront.shop.index')">
                    {{ __('storefront::storefront.continue_shopping') }}
                </x-storefront.buttons.primary-button>
            </x-storefront.empty-state>
        @else
            <div class="storefront-cart-items storefront-wishlist-account">
                <div class="storefront-cart-items__list">
                    @foreach ($wishlistItems as $item)
                        <x-storefront.wishlist.wishlist-line :item="$item" />
                    @endforeach
                </div>
            </div>
        @endif
    </x-storefront.account.layout>
@endsection
