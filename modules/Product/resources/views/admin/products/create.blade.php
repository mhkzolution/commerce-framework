@extends('layouts.admin')

@section('title', 'New Product')

@section('page')
    <x-admin.page title="New Product" description="Create a sellable product with variants and media.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog'],
                ['label' => 'Products', 'url' => route('admin.products.index')],
                ['label' => 'New product', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.products.store') }}" method="POST" class="max-w-4xl">
            @csrf
            <x-admin.form.section title="Product details" description="General information, pricing, taxonomy, media, and SEO.">
                @include('product::admin.products._form', ['product' => null, 'mediaPreviews' => []])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.products.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create product</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
