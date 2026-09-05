@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.my_account'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    @php
        $firstName = collect(explode(' ', trim((string) $customer->name)))->filter()->first() ?: $customer->name;
    @endphp

    <x-storefront.account.shell
        :customer="$customer"
        :title="__('storefront::storefront.account_welcome', ['name' => $firstName])"
        :description="__('storefront::storefront.account_overview_description')"
        section="dashboard"
    >
        <div class="storefront-account-overview">
            <a href="{{ route('storefront.account.orders') }}" class="storefront-account-overview__card">
                <span class="storefront-account-overview__label">{{ __('storefront::storefront.orders') }}</span>
                <strong class="storefront-account-overview__value">{{ $orderCount }}</strong>
                <span class="storefront-account-overview__hint">{{ __('storefront::storefront.view_orders') }}</span>
            </a>
            <a href="{{ route('storefront.account.addresses') }}" class="storefront-account-overview__card">
                <span class="storefront-account-overview__label">{{ __('storefront::storefront.addresses') }}</span>
                <strong class="storefront-account-overview__value">{{ $addressCount }}</strong>
                <span class="storefront-account-overview__hint">{{ __('storefront::storefront.manage_addresses') }}</span>
            </a>
            <a href="{{ route('storefront.account.wishlist') }}" class="storefront-account-overview__card">
                <span class="storefront-account-overview__label">{{ __('storefront::storefront.wishlist') }}</span>
                <strong class="storefront-account-overview__value">{{ $wishlistCount }}</strong>
                <span class="storefront-account-overview__hint">{{ __('storefront::storefront.view_wishlist') }}</span>
            </a>
        </div>

        <section class="storefront-account-section">
            <div class="storefront-account-section__header">
                <h2 class="storefront-panel__title">{{ __('storefront::storefront.recent_orders') }}</h2>
                <a href="{{ route('storefront.account.orders') }}" class="storefront-link">{{ __('storefront::storefront.view_all_orders') }}</a>
            </div>

            @if ($orders !== null)
                @include('customers::storefront.account._orders_table', [
                    'orders' => $orders,
                    'orderStatuses' => $orderStatuses,
                ])
            @else
                <p class="storefront-muted">{{ __('storefront::storefront.no_orders') }}</p>
            @endif
        </section>
    </x-storefront.account.shell>
@endsection
