@extends('layouts.admin')

@section('title', 'Edit Product')

@section('page')
    <x-admin.page
        :title="$product->name"
        :description="$product->slug"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog'],
                ['label' => 'Products', 'url' => route('admin.products.index')],
                ['label' => $product->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                @csrf
                @method('DELETE')
                <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
            </form>
        </x-slot:secondaryActions>

        @include('product::admin.products._workspace', [
            'mode' => 'edit',
            'product' => $product,
            'initialState' => $initialState ?? [],
        ])
    </x-admin.page>
@endsection
