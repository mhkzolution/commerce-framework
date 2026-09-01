@extends('layouts.admin')

@section('title', 'New Product')

@section('page')
    <x-admin.page title="New Product" description="Create a product with variants, media, and merchandising.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog'],
                ['label' => 'Products', 'url' => route('admin.products.index')],
                ['label' => 'New product', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        @include('product::admin.products._workspace', [
            'mode' => 'create',
            'product' => null,
        ])
    </x-admin.page>
@endsection
