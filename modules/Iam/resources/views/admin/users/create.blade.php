@extends('layouts.admin')

@section('title', 'New User')

@section('page')
    <x-admin.page title="New User" description="Create an admin account and assign roles.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Identity'],
                ['label' => 'Users', 'url' => route('admin.iam.users.index')],
                ['label' => 'New user', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.form.shell action="{{ route('admin.iam.users.store') }}" method="POST" class="max-w-2xl">
            @csrf
            <x-admin.form.section title="Account details">
                @include('iam::admin.users._form', ['statuses' => $statuses, 'roles' => $roles])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.iam.users.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Create user</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>
    </x-admin.page>
@endsection
