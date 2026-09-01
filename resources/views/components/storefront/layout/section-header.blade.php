@props([
    'title',
    'description' => null,
])

<header {{ $attributes->merge(['class' => 'storefront-section-header storefront-page-header']) }}>
    <div>
        <h1 class="storefront-section-header__title storefront-page-header__title">{{ $title }}</h1>
        @if ($description)
            <p class="storefront-section-header__description storefront-page-header__description">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="storefront-section-header__actions storefront-page-header__actions">
            {{ $actions }}
        </div>
    @endif
</header>
