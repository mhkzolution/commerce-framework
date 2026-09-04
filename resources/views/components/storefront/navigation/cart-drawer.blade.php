@props([
    'cart' => null,
    'cartUrl' => '/cart',
])

@php
    use Commerce\Cart\DTO\CartData;

    $cart = $cart instanceof CartData ? $cart : null;
@endphp

<x-storefront.navigation.drawer id="cart" :label="__('storefront::storefront.cart')">
    <x-slot:header>
        <h2 class="storefront-drawer__title">{{ __('storefront::storefront.cart') }}</h2>
        <button type="button" class="storefront-drawer__close" data-drawer-close="cart" data-drawer-close-trigger aria-label="{{ __('storefront::storefront.close') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>
    </x-slot:header>

    @if ($cart === null || $cart->lines === [])
        <p class="storefront-drawer__empty">{{ __('storefront::storefront.cart_empty') }}</p>
    @else
        @foreach ($cart->lines as $line)
            <article class="storefront-drawer-line">
                <div>
                    @if ($line->imageUrl)
                        <img src="{{ $line->imageUrl }}" alt="" class="storefront-drawer-line__image" loading="lazy" decoding="async">
                    @else
                        <div class="storefront-drawer-line__placeholder">{{ __('storefront::storefront.no_image') }}</div>
                    @endif
                </div>
                <div>
                    <span class="storefront-drawer-line__name">{{ $line->name }}</span>
                    <p class="storefront-drawer-line__meta">{{ __('storefront::storefront.quantity') }}: {{ $line->quantity }}</p>
                    <p class="storefront-drawer-line__price">
                        {{ number_format($line->lineTotal / 100, 2) }} {{ $cart->currency }}
                    </p>
                </div>
            </article>
        @endforeach
    @endif

    <x-slot:footer>
        @if ($cart !== null && $cart->lines !== [])
            <div class="storefront-drawer__subtotal">
                <span>{{ __('storefront::storefront.subtotal') }}</span>
                <strong>{{ number_format($cart->subtotal / 100, 2) }} {{ $cart->currency }}</strong>
            </div>
        @endif

        <a href="{{ $cartUrl }}" class="storefront-drawer__cta">
            {{ __('storefront::storefront.view_cart') }}
        </a>
    </x-slot:footer>
</x-storefront.navigation.drawer>
