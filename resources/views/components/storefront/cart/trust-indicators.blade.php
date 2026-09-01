@props([
    'indicators' => null,
])

@php
    $indicators ??= config('cart.storefront.trust_indicators', []);
    $labelKey = static fn (string $key): string => match ($key) {
        'secure' => 'storefront::storefront.trust_secure',
        'returns' => 'storefront::storefront.trust_returns',
        'support' => 'storefront::storefront.trust_support',
        default => 'storefront::storefront.secure_checkout',
    };
@endphp

@if ($indicators !== [])
    <ul {{ $attributes->merge(['class' => 'storefront-trust']) }}>
        @foreach ($indicators as $indicator)
            <li class="storefront-trust__item">
                <span class="storefront-trust__icon storefront-trust__icon--{{ $indicator['key'] ?? 'secure' }}" aria-hidden="true"></span>
                <span>{{ __($labelKey($indicator['key'] ?? 'secure')) }}</span>
            </li>
        @endforeach
    </ul>
@endif
