@extends('layouts.admin')

@section('title', 'Catalog')

@section('page')
    <x-admin.page title="Catalog" description="Taxonomy and product attribute infrastructure.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['Categories', 'Organize products in a hierarchy', route('admin.catalog.categories.index'), 'collection'],
                ['Brands', 'Manage product brands', route('admin.catalog.brands.index'), 'tag'],
                ['Tags', 'Flexible labeling', route('admin.catalog.tags.index'), 'tag'],
                ['Attributes', 'Define product properties', route('admin.catalog.attributes.index'), 'cube'],
                ['Attribute Sets', 'Group attributes for products', route('admin.catalog.attribute-sets.index'), 'cube'],
            ] as [$title, $description, $url, $icon])
                <a href="{{ $url }}" class="rounded-xl border border-border bg-card p-6 shadow-sm transition hover:border-primary">
                    <div class="flex items-center gap-3">
                        <x-admin.icon :name="$icon" class="h-5 w-5 text-muted" />
                        <h2 class="text-lg font-medium text-text">{{ $title }}</h2>
                    </div>
                    <p class="mt-2 text-sm text-muted">{{ $description }}</p>
                </a>
            @endforeach
        </div>
    </x-admin.page>
@endsection
