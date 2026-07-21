@extends('layouts.admin')

@section('title', 'New Attribute')

@section('page')
    <x-admin.page title="New Attribute">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'url' => route('admin.catalog.index')],
                ['label' => 'Attributes', 'url' => route('admin.catalog.attributes.index')],
                ['label' => 'New attribute', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-admin.form.shell action="{{ route('admin.catalog.attributes.store') }}" method="POST" class="max-w-2xl">
            @csrf
            <x-admin.form.section title="Attribute details">
                @include('catalog::admin.attributes._form', ['attribute' => null])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.catalog.attributes.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create attribute</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
