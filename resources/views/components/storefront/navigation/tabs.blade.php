@props([
    'label' => null,
])

<div {{ $attributes->merge(['class' => 'storefront-tabs']) }} data-tabs>
    @if (isset($nav))
        <div class="storefront-tabs__nav" role="tablist">
            {{ $nav }}
        </div>
    @endif

    <div class="storefront-tabs__panels">
        {{ $slot }}
    </div>
</div>
