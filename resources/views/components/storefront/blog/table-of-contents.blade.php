@props([
    'items' => [],
])

@if (count($items) > 1)
    <nav {{ $attributes->merge(['class' => 'storefront-toc']) }} aria-label="{{ __('cms::blog.table_of_contents') }}">
        <h2 class="storefront-toc__title">{{ __('cms::blog.table_of_contents') }}</h2>
        <ol class="storefront-toc__list">
            @foreach ($items as $item)
                <li class="storefront-toc__item storefront-toc__item--level-{{ $item['level'] }}">
                    <a href="#{{ $item['id'] }}" class="storefront-toc__link" data-toc-link="{{ $item['id'] }}">{{ $item['label'] }}</a>
                </li>
            @endforeach
        </ol>
    </nav>
@endif
