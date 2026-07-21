@extends('layouts.admin')

@section('title', 'Add tax rate')

@section('page')
    <x-admin.page title="Add tax rate" description="Define a tax rule for checkout.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Marketing'],
                ['label' => 'Tax rates', 'url' => route('admin.tax.index')],
                ['label' => 'Add rate', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.tax.store') }}" method="POST" class="max-w-3xl">
            @csrf
            <x-admin.form.section title="Rate details">
                @include('tax::admin._form')
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.tax.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create rate</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
