@props(['current' => 'cart'])

@php
    $steps = [
        'cart' => [
            'label' => __('storefront::storefront.checkout_step_cart'),
            'url' => route('storefront.cart.index'),
        ],
        'checkout' => [
            'label' => __('storefront::storefront.checkout_step_checkout'),
            'url' => route('storefront.checkout'),
        ],
        'payment' => [
            'label' => __('storefront::storefront.checkout_step_payment'),
            'url' => null,
        ],
        'complete' => [
            'label' => __('storefront::storefront.checkout_step_complete'),
            'url' => null,
        ],
    ];
    $keys = array_keys($steps);
    $currentIndex = array_search($current, $keys, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
@endphp

<nav class="storefront-checkout-progress" aria-label="{{ __('storefront::storefront.checkout') }}">
    <ol class="storefront-checkout-progress__list">
        @foreach ($steps as $key => $step)
            @php
                $index = (int) array_search($key, $keys, true);
                $state = $index < $currentIndex ? 'complete' : ($index === $currentIndex ? 'current' : 'upcoming');
            @endphp
            <li class="storefront-checkout-progress__step storefront-checkout-progress__step--{{ $state }}">
                @if ($state !== 'upcoming' && $key !== $current && is_string($step['url']))
                    <a href="{{ $step['url'] }}" class="storefront-checkout-progress__label">{{ $step['label'] }}</a>
                @else
                    <span class="storefront-checkout-progress__label">{{ $step['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
