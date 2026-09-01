@props([
    'current' => 'checkout',
])

@php
    $steps = [
        'cart' => ['label' => __('storefront::storefront.checkout_progress_cart'), 'url' => route('storefront.cart.index')],
        'checkout' => ['label' => __('storefront::storefront.checkout_progress_checkout'), 'url' => null],
        'payment' => ['label' => __('storefront::storefront.checkout_progress_payment'), 'url' => null],
        'complete' => ['label' => __('storefront::storefront.checkout_progress_complete'), 'url' => null],
    ];

    $order = array_keys($steps);
    $currentIndex = array_search($current, $order, true) ?: 1;
@endphp

<nav class="storefront-checkout-progress" aria-label="{{ __('storefront::storefront.checkout_progress_checkout') }}">
    <ol class="storefront-checkout-progress__list">
        @foreach ($steps as $key => $step)
            @php
                $index = array_search($key, $order, true);
                $isComplete = $index < $currentIndex;
                $isCurrent = $key === $current;
            @endphp
            <li @class([
                'storefront-checkout-progress__item',
                'storefront-checkout-progress__item--complete' => $isComplete,
                'storefront-checkout-progress__item--current' => $isCurrent,
            ])>
                @if ($isComplete && $step['url'])
                    <a href="{{ $step['url'] }}" class="storefront-checkout-progress__link">
                        <span class="storefront-checkout-progress__marker" aria-hidden="true"></span>
                        <span class="storefront-checkout-progress__label">{{ $step['label'] }}</span>
                    </a>
                @else
                    <span class="storefront-checkout-progress__link" @if ($isCurrent) aria-current="step" @endif>
                        <span class="storefront-checkout-progress__marker" aria-hidden="true"></span>
                        <span class="storefront-checkout-progress__label">{{ $step['label'] }}</span>
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
