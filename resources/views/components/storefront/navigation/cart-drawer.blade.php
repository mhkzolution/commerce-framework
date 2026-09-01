@props([
    'cart',
    'page',
])

<x-storefront.navigation.drawer id="cart" :label="__('storefront::storefront.cart')">
    <x-slot:header>
        <h2 class="storefront-drawer__title">{{ __('storefront::storefront.cart') }}</h2>
        <button type="button" class="storefront-drawer__close" data-drawer-close="cart" data-drawer-close-trigger aria-label="{{ __('storefront::storefront.close') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>
    </x-slot:header>

    @if ($cart->lines === [])
        <p class="storefront-drawer__empty" data-cart-empty>{{ __('storefront::storefront.empty_cart') }}</p>
    @else
        @foreach ($page->lines as $line)
            @php
                $resolved = $line->line;
                $productUrl = $line->productSlug !== ''
                    ? route('storefront.products.show', $line->productSlug)
                    : null;
            @endphp
            <article class="storefront-drawer-line">
                <div>
                    @if ($line->imageUrl)
                        <img src="{{ $line->imageUrl }}" alt="" class="storefront-drawer-line__image" loading="lazy" decoding="async">
                    @else
                        <div class="storefront-drawer-line__placeholder">{{ __('storefront::storefront.no_image') }}</div>
                    @endif
                </div>
                <div>
                    @if ($productUrl)
                        <a href="{{ $productUrl }}" class="storefront-drawer-line__name">{{ $resolved->name }}</a>
                    @else
                        <span class="storefront-drawer-line__name">{{ $resolved->name }}</span>
                    @endif
                    @if ($line->variantLabel)
                        <p class="storefront-drawer-line__meta">{{ $line->variantLabel }}</p>
                    @endif
                    <p class="storefront-drawer-line__meta">{{ __('storefront::storefront.quantity') }}: {{ $resolved->quantity }}</p>
                    <p class="storefront-drawer-line__price">
                        <x-storefront.commerce.price :amount="$resolved->lineTotal" :currency="$cart->currency" minor />
                    </p>
                </div>
            </article>
        @endforeach
    @endif

    <x-slot:footer>
        @if ($cart->lines !== [])
            <div class="storefront-drawer__subtotal">
                <span>{{ __('storefront::storefront.subtotal') }}</span>
                <strong>
                    <x-storefront.commerce.price :amount="$cart->subtotal" :currency="$cart->currency" minor />
                </strong>
            </div>
        @endif

        <x-storefront.buttons.primary-button :href="route('storefront.cart.index')" class="w-full justify-center">
            {{ __('storefront::storefront.view_cart') }}
        </x-storefront.buttons.primary-button>
    </x-slot:footer>
</x-storefront.navigation.drawer>
