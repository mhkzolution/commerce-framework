@props([
    'filters',
    'categories' => [],
    'archiveTitle' => null,
    'archiveDescription' => null,
])

@php
    $archiveTitle = $archiveTitle ?: __('cms::blog.title');
    $archiveDescription = $archiveDescription ?: __('cms::blog.description');
    $query = $filters->toQueryArray();
@endphp

<header {{ $attributes->merge(['class' => 'storefront-blog-header']) }}>
    <div class="storefront-blog-header__intro">
        <h1 class="storefront-blog-header__title">{{ $archiveTitle }}</h1>
        <p class="storefront-blog-header__description">{{ $archiveDescription }}</p>
    </div>

    <div class="storefront-blog-toolbar">
        <form method="GET" action="{{ route('storefront.cms.posts.index') }}" class="storefront-blog-toolbar__search" data-blog-search-form>
            @foreach (collect($query)->except('search') as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <label class="storefront-blog-toolbar__search-field">
                <span class="sr-only">{{ __('cms::blog.search_placeholder') }}</span>
                <input
                    type="search"
                    name="search"
                    value="{{ $filters->search }}"
                    placeholder="{{ __('cms::blog.search_placeholder') }}"
                    class="storefront-blog-search"
                    data-blog-search-input
                    autocomplete="off"
                >
            </label>
        </form>

        @if (count($categories) > 0)
            <nav class="storefront-blog-categories" aria-label="{{ __('cms::blog.categories') }}">
                <div class="storefront-blog-categories__track">
                    <a
                        href="{{ route('storefront.cms.posts.index', collect($query)->except('category')->all()) }}"
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
                            href="{{ route('storefront.cms.categories.show', array_merge(['slug' => $slug], collect($query)->except('category')->all())) }}"
                            class="storefront-blog-categories__pill {{ $filters->category === $slug ? 'storefront-blog-categories__pill--active' : '' }}"
                        >
                            {{ $name }}
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif

        <x-storefront.forms.sort-dropdown
            :action="url()->current()"
            name="sort"
            :value="$filters->sort"
            :options="[
                'latest' => __('cms::blog.sort_latest'),
                'popular' => __('cms::blog.sort_popular'),
            ]"
            class="storefront-blog-toolbar__sort"
        >
            @foreach (collect($query)->except('sort') as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
        </x-storefront.forms.sort-dropdown>
    </div>
</header>
