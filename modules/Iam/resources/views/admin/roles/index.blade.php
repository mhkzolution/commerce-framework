@extends('layouts.admin')

@section('title', 'Roles')

@section('page')
    <x-admin.page title="Roles" description="Group permissions into assignable roles.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Identity'],
                ['label' => 'Roles', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.iam.roles.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                New role
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <form method="GET" class="max-w-md">
                            <x-admin.search-input name="search" placeholder="Search name or code" />
                        </form>
                    </x-slot:search>
                </x-admin.table.toolbar>
            </x-slot:toolbar>

            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Users</th>
                    <th class="px-4 py-3">Permissions</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($roles as $role)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">
                        {{ $role->name }}
                        @if ($role->is_system)
                            <x-admin.badge variant="info" class="ml-2">System</x-admin.badge>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-muted">{{ $role->code }}</td>
                    <td class="px-4 py-3 text-muted">{{ $role->users_count }}</td>
                    <td class="px-4 py-3 text-muted">{{ $role->permissions_count }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.iam.roles.edit', $role)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-muted">No roles found.</td>
                </tr>
            @endforelse

            @if ($roles->hasPages())
                <x-slot:pagination>{{ $roles->withQueryString()->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
