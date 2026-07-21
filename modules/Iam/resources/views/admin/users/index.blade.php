@extends('layouts.admin')

@section('title', 'Users')

@section('page')
    <x-admin.page title="Users" description="Manage admin accounts, roles, and access.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Identity'],
                ['label' => 'Users', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.iam.users.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                New user
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <form method="GET" class="max-w-md">
                            <x-admin.search-input name="search" placeholder="Search name or email" />
                        </form>
                    </x-slot:search>
                    <x-slot:filters>
                        <form method="GET" class="flex flex-wrap items-center gap-2">
                            @if (request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <select name="status" class="cf-input py-2" onchange="this.form.submit()">
                                <option value="">All statuses</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </x-slot:filters>
                </x-admin.table.toolbar>
            </x-slot:toolbar>

            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Roles</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($users as $user)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $user->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$user->status === 'active' ? 'published' : 'archived'">
                            {{ $statuses[$user->status] ?? $user->status }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-text-secondary">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.iam.users.edit', $user)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-muted">No users found.</td>
                </tr>
            @endforelse

            @if ($users->hasPages())
                <x-slot:pagination>{{ $users->withQueryString()->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
