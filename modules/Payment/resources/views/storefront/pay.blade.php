@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.payment'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.layout.page-container variant="narrow" class="storefront-shopper storefront-pay">
        <x-storefront.checkout.progress current="payment" />
        <h1 class="storefront-shopper__title">{{ __('storefront::storefront.complete_payment') }}</h1>

        @if ($errors->any())
            <div class="storefront-flash storefront-flash--danger">{{ $errors->first() }}</div>
        @endif

        <div class="storefront-panel storefront-stack">
            @if ($order)
                <p class="storefront-muted">{{ __('storefront::storefront.order') }} {{ $order->order_number }}</p>
                <p class="storefront-muted">
                    {{ __('storefront::storefront.order_status') }}: {{ config('orders.statuses')[$order->status] ?? $order->status }}
                </p>
            @endif
            <p class="storefront-pay__amount">{{ number_format($payment->amount / 100, 2) }} {{ $payment->currency }}</p>
            <p class="storefront-muted">
                {{ __('storefront::storefront.gateway') }}: {{ $gateway->getName() }}
            </p>

            @if ($gateway->getCode() === 'stripe' && ! empty($initiation['client_secret']))
                <div id="stripe-payment" data-client-secret="{{ $initiation['client_secret'] }}" data-publishable-key="{{ $initiation['publishable_key'] ?? '' }}">
                    <div id="payment-element"></div>
                    <button type="button" id="stripe-submit" class="storefront-btn storefront-btn--success storefront-btn--block">{{ __('storefront::storefront.pay_with_card') }}</button>
                    <p id="stripe-error" class="storefront-danger storefront-is-hidden"></p>
                </div>
                <form id="stripe-complete-form" method="POST" action="{{ route('storefront.payment.pay', $payment) }}" class="storefront-is-hidden">
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
                                    errorEl.classList.remove('storefront-is-hidden');
                                    return;
                                }

                                document.getElementById('payment-intent-input').value = paymentIntent.id;
                                document.getElementById('stripe-complete-form').submit();
                            });
                        });
                    </script>
                @else
                    <p class="storefront-muted">{{ __('storefront::storefront.stripe_key_missing') }}</p>
                @endif
            @else
                <p class="storefront-muted">{{ __('storefront::storefront.simulated_gateway') }}</p>

                <form method="POST" action="{{ route('storefront.payment.pay', $payment) }}">
                    @csrf
                    <button type="submit" class="storefront-btn storefront-btn--success storefront-btn--block">{{ __('storefront::storefront.pay_now') }}</button>
                </form>

                <form method="POST" action="{{ route('storefront.payment.fail', $payment) }}">
                    @csrf
                    <button type="submit" class="storefront-btn storefront-btn--secondary storefront-btn--block">{{ __('storefront::storefront.simulate_payment_failure') }}</button>
                </form>
            @endif
        </div>
    </x-storefront.layout.page-container>
@endsection
