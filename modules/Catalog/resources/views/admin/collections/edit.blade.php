@extends('layouts.admin')

@section('title', 'Edit Collection')

@section('page')
    <x-admin.page :title="'Edit '.$collection->name" description="Update collection details and storefront cover">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog', 'url' => route('admin.catalog.index')],
                ['label' => 'Collections', 'url' => route('admin.catalog.collections.index')],
                ['label' => $collection->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.card>
            <form method="POST" action="{{ route('admin.catalog.collections.update', $collection) }}" class="grid max-w-3xl gap-4">
                @csrf
                @method('PUT')
                @include('catalog::admin.collections._form', ['collection' => $collection])
                <div class="flex gap-3">
                    <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.catalog.collections.index')">Cancel</x-admin.button>
                </div>
            </form>
        </x-admin.card>
    </x-admin.page>
@endsection
