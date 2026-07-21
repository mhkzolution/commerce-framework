@extends('layouts.admin')

@section('title', 'Categories')

@section('page')
    <x-admin.page title="Categories" description="Hierarchical category tree">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'url' => route('admin.catalog.index')],
                ['label' => 'Categories', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.catalog.categories.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                New category
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.card>
            @if (count($tree) > 0)
                @include('catalog::admin.categories._tree', ['categories' => $tree])
            @else
                <div class="py-8 text-center text-sm text-muted">No categories yet.</div>
            @endif
        </x-admin.card>
    </x-admin.page>
@endsection
