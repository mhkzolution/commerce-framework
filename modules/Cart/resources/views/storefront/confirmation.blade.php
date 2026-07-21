@extends('cart::layouts.storefront')

@section('title', 'Order confirmed')

@section('content')
  @php
      $statuses = config('orders.statuses', []);
  @endphp
    <div class="mx-auto max-w-lg rounded-lg border border-border bg-surface p-8 shadow-sm">
        <div class="text-center">
            <h1 class="text-2xl font-semibold text-success">Thank you!</h1>
            <p class="mt-2 text-text-secondary">Your order has been placed.</p>
            <p class="mt-4 text-lg font-medium text-text">{{ $order->order_number }}</p>
            <p class="mt-1 text-sm text-muted">
                Status: {{ $statuses[$order->status] ?? $order->status }}
            </p>
            <p class="mt-1 text-sm text-muted">Total: {{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</p>
            @if ($order->shipping_method_name)
                <p class="mt-1 text-sm text-muted">
                    Shipping: {{ $order->shipping_method_name }}
                    ({{ $order->shipping_total === 0 ? 'Free' : number_format($order->shipping_total / 100, 2) }})
                </p>
            @endif
        </div>

        @if ($order->shipping_address)
            <div class="mt-6 border-t border-border pt-6 text-sm">
                <h2 class="font-medium text-text">Shipping to</h2>
                <p class="mt-2 text-text-secondary">
                    {{ $order->shipping_address['line1'] ?? '' }}@if (! empty($order->shipping_address['line2'])), {{ $order->shipping_address['line2'] }}@endif<br>
                    {{ $order->shipping_address['city'] ?? '' }}@if (! empty($order->shipping_address['state'])), {{ $order->shipping_address['state'] }}@endif {{ $order->shipping_address['postal_code'] ?? '' }}<br>
                    {{ $order->shipping_address['country_code'] ?? '' }}
                </p>
            </div>
        @endif

        <div class="mt-6 text-center">
            <a href="{{ route('storefront.shop.index') }}" class="cf-btn cf-btn--primary">Continue shopping</a>
        </div>
    </div>
@endsection
