@extends('layouts.admin')

@section('title', 'Add currency')

@section('page')
    <x-admin.page title="Add currency" description="Configure exchange rate against the base currency.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Configuration'],
                ['label' => 'Currencies', 'url' => route('admin.currencies.index')],
                ['label' => 'Add currency', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.currencies.store') }}" method="POST" class="max-w-3xl">
            @csrf
            <x-admin.form.section title="Currency details">
                @include('currency::admin._form')
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.currencies.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create currency</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
