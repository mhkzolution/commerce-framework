@props([
    'filters',
    'categories' => [],
    'categoryImageUrls' => [],
])

<nav class="storefront-category-nav" aria-label="{{ __('storefront::storefront.filter_category') }}">
    <div class="storefront-category-nav__track">
        <a
            href="{{ route('storefront.shop.index', collect($filters->toQueryArray())->except('category')->all()) }}"
            class="storefront-category-nav__pill {{ $filters->category === null ? 'storefront-category-nav__pill--active' : '' }}"
        >
            {{ __('storefront::storefront.filter_all') }}
        </a>
        @foreach ($categories as $category)
            @php
                $imageUrl = $categoryImageUrls[$category->slug] ?? null;
            @endphp
            <a
                href="{{ route('storefront.catalog.categories.show', $category->slug) }}"
                class="storefront-category-nav__pill {{ $filters->category === $category->slug ? 'storefront-category-nav__pill--active' : '' }}"
            >
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="" class="storefront-category-nav__thumb" loading="lazy" decoding="async">
                @endif
                <span>{{ $category->name }}</span>
            </a>
        @endforeach
    </div>
</nav>
