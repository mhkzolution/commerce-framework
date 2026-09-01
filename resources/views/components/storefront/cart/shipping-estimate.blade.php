@props([
    'quotes',
    'currency',
])

@if ($quotes !== [])
    <div {{ $attributes->merge(['class' => 'storefront-shipping-estimate']) }}>
        <h2 class="storefront-shipping-estimate__title">{{ __('storefront::storefront.shipping_estimate') }}</h2>
        <ul class="storefront-shipping-estimate__list">
            @foreach ($quotes as $quote)
                <li class="storefront-shipping-estimate__item">
                    <span class="storefront-shipping-estimate__name">{{ $quote->name }}</span>
                    <span class="storefront-shipping-estimate__price">
                        @if ((int) $quote->price === 0)
                            {{ __('storefront::storefront.shipping_free') }}
                        @else
                            <x-storefront.price :amount="$quote->price" :currency="$currency" minor />
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
