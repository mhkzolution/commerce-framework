@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.orders'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.account.shell
        :customer="$customer"
        :title="__('storefront::storefront.orders')"
        :description="__('storefront::storefront.orders_description')"
        section="orders"
    >
        @if ($orders !== null)
            @include('customers::storefront.account._orders_table', [
                'orders' => $orders,
                'orderStatuses' => $orderStatuses,
            ])

            @if (method_exists($orders, 'hasPages') && $orders->hasPages())
                <div class="storefront-account-pagination">
                    {{ $orders->links() }}
                </div>
            @endif
        @else
            <x-storefront.empty-state :title="__('storefront::storefront.no_orders')">
                <a href="{{ route('storefront.shop.index') }}" class="storefront-btn">{{ __('storefront::storefront.continue_shopping') }}</a>
            </x-storefront.empty-state>
        @endif
    </x-storefront.account.shell>
@endsection
