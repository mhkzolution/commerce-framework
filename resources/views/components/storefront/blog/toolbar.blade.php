@props([
    'filters',
    'categories' => [],
    'archiveTitle' => null,
    'archiveDescription' => null,
])

@php
    $archiveTitle = $archiveTitle ?: __('cms::blog.title');
    $archiveDescription = $archiveDescription ?: __('cms::blog.description');
@endphp

<div {{ $attributes->merge(['class' => 'storefront-blog-toolbar']) }}>
    <div class="storefront-blog-toolbar__intro">
        <h1 class="storefront-blog-toolbar__title">{{ $archiveTitle }}</h1>
        <p class="storefront-blog-toolbar__description">{{ $archiveDescription }}</p>
    </div>

    <form method="GET" action="{{ route('storefront.cms.posts.index') }}" class="storefront-blog-toolbar__search" data-blog-search-form>
        @if ($filters->category)
            <input type="hidden" name="category" value="{{ $filters->category }}">
        @endif
        @if ($filters->tag)
            <input type="hidden" name="tag" value="{{ $filters->tag }}">
        @endif
        @if ($filters->authorUuid)
            <input type="hidden" name="author" value="{{ $filters->authorUuid }}">
        @endif
        <x-admin.search-input
            name="search"
            :placeholder="__('cms::blog.search_placeholder')"
            :value="$filters->search"
            data-blog-search-input
            autocomplete="off"
        />
    </form>
</div>

@if (count($categories) > 0)
    <nav class="storefront-blog-categories" aria-label="{{ __('cms::blog.categories') }}">
        <div class="storefront-blog-categories__track">
            <a
                href="{{ route('storefront.cms.posts.index', collect($filters->toQueryArray())->except('category')->all()) }}"
                class="storefront-blog-categories__pill {{ $filters->category === null ? 'storefront-blog-categories__pill--active' : '' }}"
            >
                {{ __('cms::blog.all_categories') }}
            </a>
            @foreach ($categories as $category)
                @php
                    $slug = is_object($category) ? (string) $category->slug : (string) $category;
                    $name = is_object($category) ? (string) $category->name : (string) $category;
                @endphp
                <a
                    href="{{ route('storefront.cms.categories.show', $slug) }}"
                    class="storefront-blog-categories__pill {{ $filters->category === $slug ? 'storefront-blog-categories__pill--active' : '' }}"
                >
                    {{ $name }}
                </a>
            @endforeach
        </div>
    </nav>
@endif
