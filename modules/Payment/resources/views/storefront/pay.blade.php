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
        <p class="mt-1 text-sm text-muted">
            Gateway: {{ $gateway->getName() }}
        </p>

        @if ($gateway->getCode() === 'stripe' && ! empty($initiation['client_secret']))
            <div id="stripe-payment" class="mt-6" data-client-secret="{{ $initiation['client_secret'] }}" data-publishable-key="{{ $initiation['publishable_key'] ?? '' }}">
                <div id="payment-element" class="mb-4"></div>
                <button type="button" id="stripe-submit" class="cf-btn cf-btn--success w-full py-3">Pay with card</button>
                <p id="stripe-error" class="mt-2 hidden text-sm text-danger"></p>
            </div>
            <form id="stripe-complete-form" method="POST" action="{{ route('storefront.payment.pay', $payment) }}" class="hidden">
                @csrf
                <input type="hidden" name="payment_intent" id="payment-intent-input">
            </form>
            @if (! empty($initiation['publishable_key']))
                <script src="https://js.stripe.com/v3/"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const root = document.getElementById('stripe-payment');
                        if (!root) return;

                        const stripe = Stripe(root.dataset.publishableKey);
                        const elements = stripe.elements({ clientSecret: root.dataset.clientSecret });
                        const paymentElement = elements.create('payment');
                        paymentElement.mount('#payment-element');

                        document.getElementById('stripe-submit').addEventListener('click', async () => {
                            const { error, paymentIntent } = await stripe.confirmPayment({
                                elements,
                                redirect: 'if_required',
                            });

                            if (error) {
                                const errorEl = document.getElementById('stripe-error');
                                errorEl.textContent = error.message;
                                errorEl.classList.remove('hidden');
                                return;
                            }

                            document.getElementById('payment-intent-input').value = paymentIntent.id;
                            document.getElementById('stripe-complete-form').submit();
                        });
                    });
                </script>
            @else
                <p class="mt-4 text-sm text-muted">Stripe publishable key is not configured. Set STRIPE_PUBLISHABLE_KEY to enable card payments.</p>
            @endif
        @else
            <p class="mt-1 text-sm text-muted">Simulated payment gateway — stock is reserved after you pay.</p>

            <form method="POST" action="{{ route('storefront.payment.pay', $payment) }}" class="mt-6">
                @csrf
                <button type="submit" class="cf-btn cf-btn--success w-full py-3">Pay now</button>
            </form>

            <form method="POST" action="{{ route('storefront.payment.fail', $payment) }}" class="mt-3">
                @csrf
                <button type="submit" class="cf-btn cf-btn--outline w-full border-danger text-danger hover:bg-danger-subtle">Simulate payment failure</button>
            </form>
        @endif
    </div>
@endsection
