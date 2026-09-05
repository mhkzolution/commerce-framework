@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.wishlist'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.account.shell
        :customer="$customer"
        :title="__('storefront::storefront.wishlist')"
        :description="__('storefront::storefront.wishlist_description')"
        section="wishlist"
    >
        @if ($items !== [])
            <div class="storefront-account-products">
                <ul class="storefront-account-products__grid">
                    @foreach ($items as $item)
                        <li class="storefront-account-product">
                            <a href="{{ $item['url'] }}" class="storefront-account-product__media">
                                @if (! empty($item['image_url']))
                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="storefront-account-product__image">
                                @else
                                    <span class="storefront-account-product__placeholder">{{ __('storefront::storefront.no_image') }}</span>
                                @endif
                            </a>
                            <div class="storefront-account-product__body">
                                <a href="{{ $item['url'] }}" class="storefront-account-product__name">{{ $item['name'] }}</a>
                                @if (! empty($item['variant_label']))
                                    <p class="storefront-muted">{{ $item['variant_label'] }}</p>
                                @endif
                                <p class="storefront-account-product__price">
                                    {{ number_format(((int) $item['price']) / 100, 2) }} {{ $item['currency'] }}
                                </p>
                                <form method="POST" action="{{ route('storefront.account.wishlist.items.destroy') }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                                    @if (! empty($item['variant_id']))
                                        <input type="hidden" name="variant_id" value="{{ $item['variant_id'] }}">
                                    @endif
                                    <button type="submit" class="storefront-btn storefront-btn--danger">{{ __('storefront::storefront.remove') }}</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <x-storefront.empty-state :title="__('storefront::storefront.wishlist_empty')">
                <a href="{{ route('storefront.shop.index') }}" class="storefront-btn">{{ __('storefront::storefront.continue_shopping') }}</a>
            </x-storefront.empty-state>
        @endif
    </x-storefront.account.shell>
@endsection
