@props([
    'title',
    'as' => 'section',
])

<{{ $as }} {{ $attributes->merge(['class' => 'storefront-section']) }}>
    @if (isset($header))
        <div class="storefront-section__header">
            {{ $header }}
        </div>
    @elseif ($title)
        <x-storefront.layout.section-header :title="$title" class="storefront-section__header" />
    @endif

    <div class="storefront-section__content">
        {{ $slot }}
    </div>
</{{ $as }}>
