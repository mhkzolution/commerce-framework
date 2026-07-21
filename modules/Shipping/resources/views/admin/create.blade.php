@extends('layouts.admin')

@section('title', 'Add shipping method')

@section('page')
    <x-admin.page title="Add shipping method" description="Configure delivery rate and availability rules.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Configuration'],
                ['label' => 'Shipping', 'url' => route('admin.shipping.index')],
                ['label' => 'Add method', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.shipping.store') }}" method="POST" class="max-w-3xl">
            @csrf
            <x-admin.form.section title="Method details">
                @include('shipping::admin._form')
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.shipping.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create method</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
