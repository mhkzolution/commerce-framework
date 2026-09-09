@php
    $isExact = (string) $selected === (string) $category->slug;
    $childActive = false;
    foreach ($category->children as $child) {
        if ((string) $selected === (string) $child->slug) {
            $childActive = true;
            break;
        }
    }
    $isActive = $isExact || $childActive;
@endphp

<li class="storefront-category-tree__item">
    <a
        href="{{ $category->url ?? route('storefront.shop.index', ['category' => $category->slug]) }}"
        class="storefront-filters__tree-link {{ $isActive ? 'storefront-filters__tree-link--active' : '' }}"
    >
        {{ $category->name }}
    </a>
    @if ($category->children !== [])
        <ul class="storefront-category-tree__list storefront-category-tree__list--children">
            @foreach ($category->children as $child)
                @include('cart::storefront.partials.category-tree-node', [
                    'category' => $child,
                    'selected' => $selected,
                ])
            @endforeach
        </ul>
    @endif
</li>
