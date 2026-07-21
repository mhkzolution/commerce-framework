@extends('cart::layouts.storefront')

@section('title', 'Payment')

@section('content')
    <h1 class="text-2xl font-semibold text-text">Complete payment</h1>

    @if ($errors->any())
        <div class="cf-flash cf-flash--danger mt-4">{{ $errors->first() }}</div>
    @endif

    <div class="mt-6 max-w-lg rounded-lg border border-border bg-surface p-6 shadow-sm">
        @if ($order)
            <p class="text-sm text-muted">Order {{ $order->order_number }}</p>
            <p class="mt-1 text-xs text-muted">
                Order status: {{ config('orders.statuses')[$order->status] ?? $order->status }}
            </p>
        @endif
        <p class="mt-2 text-3xl font-semibold text-text">{{ number_format($payment->amount / 100, 2) }} {{ $payment->currency }}</p>
        <p class="mt-1 text-sm text-muted">Simulated payment gateway — stock is reserved after you pay.</p>

        <form method="POST" action="{{ route('storefront.payment.pay', $payment) }}" class="mt-6">
            @csrf
            <button type="submit" class="cf-btn cf-btn--success w-full py-3">Pay now</button>
        </form>

        <form method="POST" action="{{ route('storefront.payment.fail', $payment) }}" class="mt-3">
            @csrf
            <button type="submit" class="cf-btn cf-btn--outline w-full border-danger text-danger hover:bg-danger-subtle">Simulate payment failure</button>
        </form>
    </div>
@endsection
