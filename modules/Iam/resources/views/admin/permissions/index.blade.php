@extends('layouts.admin')

@section('title', 'Permissions')

@section('page')
    <x-admin.page title="Permissions" description="Read-only registry of all module permissions.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Identity'],
                ['label' => 'Permissions', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:filters>
                        <form method="GET" class="flex flex-wrap items-center gap-2">
                            <select name="module" class="cf-input py-2" onchange="this.form.submit()">
                                <option value="">All modules</option>
                                @foreach ($modules as $module)
                                    <option value="{{ $module }}" @selected(request('module') === $module)>{{ ucfirst($module) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </x-slot:filters>
                </x-admin.table.toolbar>
            </x-slot:toolbar>

            @forelse ($permissionsByModule as $module => $permissions)
                <x-admin.card :title="ucfirst($module)" class="mb-4">
                    <x-admin.table.shell>
                        <x-slot:head>
                            <tr class="text-left text-xs uppercase tracking-wide text-muted">
                                <th class="px-4 py-3">Permission</th>
                                <th class="px-4 py-3">Label</th>
                                <th class="px-4 py-3">Group</th>
                            </tr>
                        </x-slot:head>

                        @foreach ($permissions as $permission)
                            <tr>
                                <td class="px-4 py-3 font-mono text-sm text-text">{{ $permission->name }}</td>
                                <td class="px-4 py-3 text-text">{{ $permission->label }}</td>
                                <td class="px-4 py-3 text-muted">{{ $permission->group ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </x-admin.table.shell>
                </x-admin.card>
            @empty
                <p class="px-4 py-8 text-center text-muted">No permissions registered.</p>
            @endforelse
        </x-admin.table.shell>
    </x-admin.page>
@endsection
