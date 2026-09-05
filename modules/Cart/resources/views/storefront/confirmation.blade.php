@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.order_confirmed'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    @php
        $statuses = config('orders.statuses', []);
        $customer = auth('customer')->user();
        $canViewAccountOrder = $customer && $order->customer_uuid === $customer->uuid;
        $viewOrderUrl = $canViewAccountOrder
            ? route('storefront.account.orders.show', $order)
            : route('storefront.checkout.confirmation', $order);
        $addressLines = \Commerce\Orders\Support\AddressFormatter::lines($order->shipping_address);
    @endphp
    <x-storefront.layout.page-container variant="narrow" class="storefront-shopper storefront-confirmation storefront-confirmation--success">
        <x-storefront.checkout.progress current="complete" />

        <div class="storefront-panel storefront-stack storefront-confirmation__card">
            <div class="storefront-confirmation__badge" aria-hidden="true">✓</div>
            <h1 class="storefront-shopper__title">{{ __('storefront::storefront.thank_you') }}</h1>
            <p class="storefront-muted">{{ __('storefront::storefront.order_placed') }}</p>
            <p class="storefront-confirmation__number">{{ $order->order_number }}</p>
            <p class="storefront-muted">
                {{ __('storefront::storefront.status') }}: {{ $statuses[$order->status] ?? $order->status }}
            </p>
            <p class="storefront-confirmation__total">
                {{ __('storefront::storefront.total') }}: {{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}
            </p>

            @if ($addressLines !== [])
                <div>
                    <h2 class="storefront-panel__title">{{ __('storefront::storefront.shipping_to') }}</h2>
                    <p class="storefront-muted">
                        {!! implode('<br>', array_map('e', $addressLines)) !!}
                    </p>
                </div>
            @endif

            <div id="order-items">
                <h2 class="storefront-panel__title">{{ __('storefront::storefront.items_purchased') }}</h2>
                <ul class="storefront-stack">
                    @foreach ($order->lineItems as $line)
                        <li class="storefront-summary-row">
                            <span>{{ $line->name }} × {{ $line->quantity }}</span>
                            <span>{{ number_format($line->line_total / 100, 2) }} {{ $order->currency }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="storefront-confirmation__actions">
                <a href="{{ $viewOrderUrl }}" class="storefront-btn">{{ __('storefront::storefront.view_order') }}</a>
                <a href="{{ route('storefront.shop.index') }}" class="storefront-btn storefront-btn--secondary">{{ __('storefront::storefront.continue_shopping') }}</a>
            </div>
        </div>
    </x-storefront.layout.page-container>
@endsection
