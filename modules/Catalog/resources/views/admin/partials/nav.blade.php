<nav class="cf-subnav" aria-label="Catalog sections">
    @foreach ([
        ['Overview', 'admin.catalog.index'],
        ['Categories', 'admin.catalog.categories.*'],
        ['Brands', 'admin.catalog.brands.*'],
        ['Tags', 'admin.catalog.tags.*'],
        ['Attributes', 'admin.catalog.attributes.*'],
        ['Attribute Sets', 'admin.catalog.attribute-sets.*'],
    ] as [$label, $routePattern])
        @php
            $url = match ($label) {
                'Overview' => route('admin.catalog.index'),
                'Categories' => route('admin.catalog.categories.index'),
                'Brands' => route('admin.catalog.brands.index'),
                'Tags' => route('admin.catalog.tags.index'),
                'Attributes' => route('admin.catalog.attributes.index'),
                'Attribute Sets' => route('admin.catalog.attribute-sets.index'),
            };
        @endphp
        <a
            href="{{ $url }}"
            @class(['cf-subnav-link', 'is-active' => request()->routeIs($routePattern)])
        >{{ $label }}</a>
    @endforeach
</nav>
