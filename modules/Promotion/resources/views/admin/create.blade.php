@extends('layouts.admin')

@section('title', 'Add promotion')

@section('page')
    <x-admin.page title="Add promotion" description="Create a coupon or cart discount.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Marketing'],
                ['label' => 'Promotions', 'url' => route('admin.promotions.index')],
                ['label' => 'Add promotion', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.promotions.store') }}" method="POST" class="max-w-3xl">
            @csrf
            <x-admin.form.section title="Promotion details">
                @include('promotion::admin._form')
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.promotions.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create promotion</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
