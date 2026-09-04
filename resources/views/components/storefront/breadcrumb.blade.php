{{--
Temporary adapter for Blog UI Refresh (v1.3.0)

This component intentionally does not depend on
commerce-framework-v1 storefront primitives.

Replace with shared storefront primitives
when the design system is merged.
--}}
@props([
    'items' => [],
    'ariaLabel' => null,
])

@if (count($items) > 0)
    <nav {{ $attributes->merge(['class' => 'storefront-breadcrumb', 'aria-label' => $ariaLabel ?: __('cms::blog.breadcrumb')]) }}>
        <ol class="storefront-breadcrumb__list">
            @foreach ($items as $item)
                <li class="storefront-breadcrumb__item">
                    @if (! empty($item['url']))
                        <a href="{{ $item['url'] }}" class="storefront-breadcrumb__link">{{ $item['label'] }}</a>
                    @else
                        <span class="storefront-breadcrumb__current" aria-current="page">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
