@extends('layouts.admin')

@section('title', 'New Customer')

@section('page')
    <x-admin.page title="New Customer" description="Create a buyer profile for orders and account access.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Sales'],
                ['label' => 'Customers', 'url' => route('admin.customers.index')],
                ['label' => 'New customer', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.customers.store') }}" method="POST" class="max-w-2xl">
            @csrf
            <x-admin.form.section title="Customer details">
                @include('customers::admin._form', ['statuses' => $statuses])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.customers.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create customer</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
