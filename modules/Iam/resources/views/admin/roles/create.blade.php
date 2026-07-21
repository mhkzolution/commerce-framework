@extends('layouts.admin')

@section('title', 'New Role')

@section('page')
    <x-admin.page title="New Role" description="Create a role and assign permissions.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Identity'],
                ['label' => 'Roles', 'url' => route('admin.iam.roles.index')],
                ['label' => 'New role', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.iam.roles.store') }}" method="POST" class="max-w-3xl">
            @csrf
            <x-admin.form.section title="Role details">
                @include('iam::admin.roles._form', ['permissionsByModule' => $permissionsByModule])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.iam.roles.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create role</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
