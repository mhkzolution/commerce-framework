@extends('layouts.admin')

@section('title', 'New Category')

@section('page')
    <x-admin.page title="New Category">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'url' => route('admin.catalog.index')],
                ['label' => 'Categories', 'url' => route('admin.catalog.categories.index')],
                ['label' => 'New category', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-admin.form.shell action="{{ route('admin.catalog.categories.store') }}" method="POST" class="max-w-2xl">
            @csrf
            <x-admin.form.section title="Category details">
                @include('catalog::admin.categories._form', ['category' => null])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.catalog.categories.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create category</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
