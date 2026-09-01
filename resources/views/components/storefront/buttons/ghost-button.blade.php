@props([
    'href' => null,
    'type' => 'button',
])

<x-admin.button variant="ghost" :href="$href" :type="$type" {{ $attributes->merge(['class' => 'storefront-btn storefront-btn--ghost']) }}>
    {{ $slot }}
</x-admin.button>
