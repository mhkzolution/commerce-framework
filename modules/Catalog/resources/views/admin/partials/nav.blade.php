<nav class="cf-subnav" aria-label="{{ __('catalog::admin.catalog_sections') }}">
    @foreach ([
        ['catalog::admin.overview', 'admin.catalog.index'],
        ['catalog::admin.categories', 'admin.catalog.categories.*'],
        ['catalog::admin.brands', 'admin.catalog.brands.*'],
        ['catalog::admin.collections', 'admin.catalog.collections.*'],
        ['catalog::admin.tags', 'admin.catalog.tags.*'],
        ['product::workspace.variant_options_nav', 'admin.catalog.variant-options.*'],
        ['catalog::admin.attributes', 'admin.catalog.attributes.*'],
        ['catalog::admin.attribute_sets', 'admin.catalog.attribute-sets.*'],
    ] as [$labelKey, $routePattern])
        @php
            $label = __($labelKey);
            $url = match ($labelKey) {
                'catalog::admin.overview' => route('admin.catalog.index'),
                'catalog::admin.categories' => route('admin.catalog.categories.index'),
                'catalog::admin.brands' => route('admin.catalog.brands.index'),
                'catalog::admin.collections' => route('admin.catalog.collections.index'),
                'catalog::admin.tags' => route('admin.catalog.tags.index'),
                'product::workspace.variant_options_nav' => route('admin.catalog.variant-options.index'),
                'catalog::admin.attributes' => route('admin.catalog.attributes.index'),
                'catalog::admin.attribute_sets' => route('admin.catalog.attribute-sets.index'),
            };
        @endphp
        <a
            href="{{ $url }}"
            @class(['cf-subnav-link', 'is-active' => request()->routeIs($routePattern)])
        >{{ $label }}</a>
    @endforeach
</nav>
