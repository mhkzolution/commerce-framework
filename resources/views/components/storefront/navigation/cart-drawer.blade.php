@props([
    'cart' => null,
    'cartUrl' => '/cart',
])

@php
    use Commerce\Cart\DTO\CartData;

    $cart = $cart instanceof CartData ? $cart : null;
    $itemCount = $cart?->itemCount ?? 0;
@endphp

<x-storefront.navigation.drawer id="cart" :label="__('storefront::storefront.cart')" data-mini-cart>
    <x-slot:header>
        <h2 class="storefront-drawer__title">
            {{ __('storefront::storefront.cart') }}
            <span class="storefront-drawer__count" data-mini-cart-count>{{ $itemCount }}</span>
        </h2>
        <button type="button" class="storefront-drawer__close" data-drawer-close="cart" data-drawer-close-trigger aria-label="{{ __('storefront::storefront.close') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>
    </x-slot:header>

    <div data-mini-cart-body>
        @if ($cart === null || $cart->lines === [])
            <p class="storefront-drawer__empty">{{ __('storefront::storefront.cart_empty') }}</p>
        @else
            @foreach ($cart->lines as $line)
                <article class="storefront-drawer-line">
                    <div>
                        @if ($line->imageUrl)
                            <x-storefront.media.img
                                :src="$line->imageUrl"
                                :srcset="$line->imageSrcset"
                                :sizes="config('media.sizes.cart')"
                                alt=""
                                class="storefront-drawer-line__image"
                            />
                        @else
                            <div class="storefront-drawer-line__placeholder">{{ __('storefront::storefront.no_image') }}</div>
                        @endif
                    </div>
                    <div class="storefront-drawer-line__content">
                        @if ($line->url)
                            <a href="{{ $line->url }}" class="storefront-drawer-line__name">{{ $line->productName ?? $line->name }}</a>
                        @else
                            <span class="storefront-drawer-line__name">{{ $line->productName ?? $line->name }}</span>
                        @endif
                        @if ($line->variantLabel)
                            <p class="storefront-drawer-line__meta">{{ $line->variantLabel }}</p>
                        @endif
                        <p class="storefront-drawer-line__price">
                            {{ number_format($line->lineTotal / 100, 2) }} {{ $cart->currency }}
                        </p>
                        <div class="storefront-drawer-line__actions">
                            <form method="POST" action="{{ route('storefront.cart.items.update', $line->purchasableUuid) }}" class="storefront-qty-stepper" data-qty-stepper data-cart-qty>
                                @csrf
                                @method('PATCH')
                                <button type="button" class="storefront-qty-stepper__btn" data-qty-dec aria-label="{{ __('storefront::storefront.decrease_quantity') }}">−</button>
                                <input type="number" name="quantity" value="{{ $line->quantity }}" min="0" max="{{ $line->available }}" class="storefront-qty-stepper__input" aria-label="{{ __('storefront::storefront.quantity') }}">
                                <button type="button" class="storefront-qty-stepper__btn" data-qty-inc aria-label="{{ __('storefront::storefront.increase_quantity') }}">+</button>
                            </form>
                            <form method="POST" action="{{ route('storefront.cart.items.destroy', $line->purchasableUuid) }}" data-cart-remove>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="storefront-btn storefront-btn--danger">{{ __('storefront::storefront.remove') }}</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        @endif
    </div>

    <x-slot:footer>
        <div data-mini-cart-footer>
            @if ($cart !== null && $cart->lines !== [])
                <div class="storefront-drawer__subtotal">
                    <span>{{ __('storefront::storefront.subtotal') }}</span>
                    <strong data-mini-cart-subtotal>{{ number_format($cart->subtotal / 100, 2) }} {{ $cart->currency }}</strong>
                </div>
                <div class="storefront-drawer__actions">
                    <a href="{{ $cartUrl }}" class="storefront-drawer__cta storefront-drawer__cta--secondary">
                        {{ __('storefront::storefront.view_cart') }}
                    </a>
                    <a href="{{ route('storefront.checkout') }}" class="storefront-drawer__cta">
                        {{ __('storefront::storefront.checkout') }}
                    </a>
                </div>
            @else
                <a href="{{ $cartUrl }}" class="storefront-drawer__cta">
                    {{ __('storefront::storefront.view_cart') }}
                </a>
            @endif
        </div>
    </x-slot:footer>
</x-storefront.navigation.drawer>
