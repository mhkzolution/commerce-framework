@extends('layouts.admin')

@section('title', 'Tenants')

@section('page')
    <x-admin.page title="Tenants" description="Manage SaaS tenants and domains.">
        <x-slot:primaryActions>
            @can('platform.tenant.manage')
                <x-admin.button variant="primary" :href="route('admin.platform.tenants.create')">New tenant</x-admin.button>
            @endcan
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Domain</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>
            @forelse ($items as $item)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $item->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $item->slug }}</td>
                    <td class="px-4 py-3 text-muted">{{ $item->domain ?: '—' }}</td>
                    <td class="px-4 py-3">{{ ucfirst($item->status) }}</td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.platform.tenants.edit', $item)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-muted">No tenants yet.</td>
                </tr>
            @endforelse
            @if ($items->hasPages())
                <x-slot:pagination>{{ $items->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
