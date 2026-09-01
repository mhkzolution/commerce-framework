@props([
    'title',
    'description' => null,
    'step' => null,
])

<section {{ $attributes->merge(['class' => 'storefront-checkout-section']) }}>
    <header class="storefront-checkout-section__header">
        @if ($step)
            <span class="storefront-checkout-section__step" aria-hidden="true">{{ $step }}</span>
        @endif
        <div class="storefront-checkout-section__titles">
            <h2 class="storefront-checkout-section__title">{{ $title }}</h2>
            @if ($description)
                <p class="storefront-checkout-section__description">{{ $description }}</p>
            @endif
        </div>
    </header>

    <div class="storefront-checkout-section__body">
        {{ $slot }}
    </div>
</section>
