@props([
    'lines',
    'currency',
])

<div {{ $attributes->merge(['class' => 'storefront-checkout-items']) }}>
    <h3 class="storefront-checkout-items__title">{{ __('storefront::storefront.checkout_items') }}</h3>
    <ul class="storefront-checkout-items__list">
        @foreach ($lines as $line)
            @php
                /** @var \Commerce\Cart\DTO\StorefrontCartLineView $line */
                $resolved = $line->line;
            @endphp
            <li class="storefront-checkout-item">
                <div class="storefront-checkout-item__media">
                    @if ($line->imageUrl)
                        <img src="{{ $line->imageUrl }}" alt="" class="storefront-checkout-item__image" loading="lazy">
                    @else
                        <span class="storefront-checkout-item__placeholder" aria-hidden="true"></span>
                    @endif
                    <span class="storefront-checkout-item__qty">{{ $resolved->quantity }}</span>
                </div>
                <div class="storefront-checkout-item__body">
                    <p class="storefront-checkout-item__name">{{ $resolved->name }}</p>
                    @if ($line->variantLabel)
                        <p class="storefront-checkout-item__variant">{{ $line->variantLabel }}</p>
                    @endif
                </div>
                <div class="storefront-checkout-item__price">
                    <x-storefront.price :amount="$resolved->lineTotal" :currency="$currency" minor />
                </div>
            </li>
        @endforeach
    </ul>
</div>
