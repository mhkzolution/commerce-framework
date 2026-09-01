@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'storefront-empty']) }}>
    <p class="storefront-empty__title">{{ $title }}</p>
    @if ($description)
        <p class="storefront-empty__description">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
