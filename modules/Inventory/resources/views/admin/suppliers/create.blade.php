@extends('layouts.admin')

@section('title', 'Add supplier')

@section('page')
    <x-admin.page title="Add supplier" description="Create a supplier for purchase orders">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
                ['label' => 'Suppliers', 'url' => route('admin.inventory.suppliers.index')],
                ['label' => 'Create', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.card class="max-w-xl">
            <form method="POST" action="{{ route('admin.inventory.suppliers.store') }}" class="grid gap-4">
                @csrf
                @include('inventory::admin.suppliers._form')
                <div>
                    <x-admin.button variant="primary" type="submit">Create supplier</x-admin.button>
                </div>
            </form>
        </x-admin.card>
    </x-admin.page>
@endsection
