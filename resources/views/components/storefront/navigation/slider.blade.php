@props([
    'autoplay' => false,
    'loop' => false,
    'lazy' => false,
    'interval' => 5000,
    'label' => null,
])

<div
    {{ $attributes->class('storefront-slider') }}
    data-storefront-slider
    data-autoplay="{{ $autoplay ? 'true' : 'false' }}"
    data-loop="{{ $loop ? 'true' : 'false' }}"
    data-lazy="{{ $lazy ? 'true' : 'false' }}"
    data-interval="{{ $interval }}"
    @if ($label) aria-label="{{ $label }}" @endif
    role="region"
>
    <div class="storefront-slider__viewport">
        <div class="storefront-slider__track" data-slider-track>
            {{ $slot }}
        </div>
    </div>
    <div class="storefront-slider__dots" data-slider-dots hidden></div>
</div>
