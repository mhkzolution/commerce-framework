@props([
    'items' => [],
])

@if (count($items) > 0)
<nav {{ $attributes->merge(['class' => 'storefront-breadcrumb', 'aria-label' => __('storefront::storefront.breadcrumb')]) }}>
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
