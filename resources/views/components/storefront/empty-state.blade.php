{{--
Temporary adapter for Blog UI Refresh (v1.3.0)

This component intentionally does not depend on
commerce-framework-v1 storefront primitives.

Replace with shared storefront primitives
when the design system is merged.
--}}
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
        <div class="storefront-empty__actions">{{ $slot }}</div>
    @endif
</div>
