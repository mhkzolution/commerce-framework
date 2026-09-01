@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

<x-admin.button :variant="$variant" :href="$href" :type="$type" {{ $attributes->merge(['class' => 'storefront-btn storefront-btn--primary']) }}>
    {{ $slot }}
</x-admin.button>
