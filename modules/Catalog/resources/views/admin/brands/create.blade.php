@extends('layouts.admin')

@section('title', 'New Brand')

@section('page')
    <x-admin.page title="New Brand">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'url' => route('admin.catalog.index')],
                ['label' => 'Brands', 'url' => route('admin.catalog.brands.index')],
                ['label' => 'New brand', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-admin.form.shell action="{{ route('admin.catalog.brands.store') }}" method="POST" class="max-w-2xl">
            @csrf
            <x-admin.form.section title="Brand details">
                @include('catalog::admin.brands._form', ['brand' => null])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.catalog.brands.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create brand</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
