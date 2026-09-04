@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.order_confirmed'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    @php
        $statuses = config('orders.statuses', []);
    @endphp
    <x-storefront.layout.page-container variant="narrow" class="storefront-shopper storefront-confirmation">
        <div class="storefront-panel storefront-stack">
            <h1 class="storefront-shopper__title">{{ __('storefront::storefront.thank_you') }}</h1>
            <p class="storefront-muted">{{ __('storefront::storefront.order_placed') }}</p>
            <p>{{ $order->order_number }}</p>
            <p class="storefront-muted">
                {{ __('storefront::storefront.status') }}: {{ $statuses[$order->status] ?? $order->status }}
            </p>
            <p class="storefront-muted">
                {{ __('storefront::storefront.total') }}: {{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}
            </p>
            @if ($order->shipping_method_name)
                <p class="storefront-muted">
                    {{ __('storefront::storefront.shipping') }}: {{ $order->shipping_method_name }}
                    ({{ $order->shipping_total === 0 ? __('storefront::storefront.free') : number_format($order->shipping_total / 100, 2) }})
                </p>
            @endif

            @if ($order->shipping_address)
                <div>
                    <h2 class="storefront-panel__title">{{ __('storefront::storefront.shipping_to') }}</h2>
                    <p class="storefront-muted">
                        {{ $order->shipping_address['line1'] ?? '' }}@if (! empty($order->shipping_address['line2'])), {{ $order->shipping_address['line2'] }}@endif<br>
                        {{ $order->shipping_address['city'] ?? '' }}@if (! empty($order->shipping_address['state'])), {{ $order->shipping_address['state'] }}@endif {{ $order->shipping_address['postal_code'] ?? '' }}<br>
                        {{ $order->shipping_address['country_code'] ?? '' }}
                    </p>
                </div>
            @endif

            <a href="{{ route('storefront.shop.index') }}" class="storefront-btn">{{ __('storefront::storefront.continue_shopping') }}</a>
        </div>
    </x-storefront.layout.page-container>
@endsection
