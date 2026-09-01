@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.account_dashboard'))

@section('content')
    <x-storefront.account.layout
        :customer="$customer"
        active="dashboard"
        :title="__('storefront::storefront.account_dashboard')"
        :description="__('storefront::storefront.account_dashboard_description')"
    >
        @session('status')
            <div class="cf-flash cf-flash--success mb-4">{{ $value }}</div>
        @endsession

        <section class="storefront-account-products" aria-label="{{ __('storefront::storefront.new_products') }}">
            @if ($newProducts->isNotEmpty())
                <div class="storefront-account-products__grid">
                    @foreach ($newProducts as $product)
                        @php
                            $variant = $product->defaultVariant();
                            $available = $variant ? ($stockLevels[$variant->uuid] ?? null) : null;
                        @endphp
                        @if ($variant)
                            <x-storefront.product-card
                                :product="$product"
                                :variant="$variant"
                                :display-currency="$displayCurrency"
                                :base-currency="$baseCurrency"
                                :currency-converter="$currencyConverter"
                                :available="$available"
                            />
                        @endif
                    @endforeach
                </div>
            @else
                <x-storefront.empty-state
                    :title="__('storefront::storefront.no_products')"
                    :description="__('storefront::storefront.account_new_products_empty')"
                >
                    <x-storefront.buttons.primary-button :href="route('storefront.shop.index')">
                        {{ __('storefront::storefront.continue_shopping') }}
                    </x-storefront.buttons.primary-button>
                </x-storefront.empty-state>
            @endif
        </section>
    </x-storefront.account.layout>
@endsection

@push('scripts')
    @vite('resources/js/storefront/shop.js')
@endpush
