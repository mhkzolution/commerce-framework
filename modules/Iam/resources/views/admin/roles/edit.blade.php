@extends('layouts.admin')

@section('title', $role->name)

@section('page')
    <x-admin.page :title="$role->name" :description="$role->code">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Identity'],
                ['label' => 'Roles', 'url' => route('admin.iam.roles.index')],
                ['label' => $role->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        @if (! $role->is_system)
            <x-slot:secondaryActions>
                <form method="POST" action="{{ route('admin.iam.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                    @csrf
                    @method('DELETE')
                    <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
                </form>
            </x-slot:secondaryActions>
        @endif

        @error('delete')
            <div class="cf-flash cf-flash--danger mb-4 max-w-3xl" role="alert">{{ $message }}</div>
        @enderror

        <x-admin.form.shell action="{{ route('admin.iam.roles.update', $role) }}" method="POST" class="max-w-3xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="Role details">
                @include('iam::admin.roles._form', ['role' => $role, 'permissionsByModule' => $permissionsByModule])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.iam.roles.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>

        <x-admin.card title="Assigned users" class="mt-6 max-w-3xl">
            <p class="text-sm text-muted">{{ $role->users()->count() }} user(s) have this role.</p>
        </x-admin.card>
    </x-admin.page>
@endsection
