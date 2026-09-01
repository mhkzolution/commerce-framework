@props([
    'title',
    'description' => null,
])

<header {{ $attributes->merge(['class' => 'storefront-auth-heading']) }}>
    <h1 class="storefront-auth-heading__title">{{ $title }}</h1>
    @if ($description)
        <p class="storefront-auth-heading__description">{{ $description }}</p>
    @endif
</header>
