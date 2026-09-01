@extends('layouts.admin')

@section('title', 'Edit supplier')

@section('page')
    <x-admin.page title="Edit supplier" :description="$supplier->name">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
                ['label' => 'Suppliers', 'url' => route('admin.inventory.suppliers.index')],
                ['label' => $supplier->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.card class="max-w-xl">
            <form method="POST" action="{{ route('admin.inventory.suppliers.update', $supplier) }}" class="grid gap-4">
                @csrf
                @method('PUT')
                @include('inventory::admin.suppliers._form', ['supplier' => $supplier])
                <div class="flex flex-wrap gap-2">
                    <x-admin.button variant="primary" type="submit">Save supplier</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.inventory.suppliers.index')">Cancel</x-admin.button>
                </div>
            </form>
        </x-admin.card>
    </x-admin.page>
@endsection
