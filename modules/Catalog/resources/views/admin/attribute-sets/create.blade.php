@extends('layouts.admin')

@section('title', 'New Attribute Set')

@section('page')
    <x-admin.page title="New Attribute Set">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'url' => route('admin.catalog.index')],
                ['label' => 'Attribute Sets', 'url' => route('admin.catalog.attribute-sets.index')],
                ['label' => 'New set', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            @include('catalog::admin.partials.nav')
        </x-slot:filters>

        <x-admin.form.shell action="{{ route('admin.catalog.attribute-sets.store') }}" method="POST" class="max-w-2xl">
            @csrf
            <x-admin.form.section title="Set details">
                @include('catalog::admin.attribute-sets._form', ['attributeSet' => null])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.catalog.attribute-sets.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create set</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
