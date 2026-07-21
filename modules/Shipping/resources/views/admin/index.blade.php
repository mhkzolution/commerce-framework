@extends('layouts.admin')

@section('title', 'Shipping')

@section('page')
    <x-admin.page title="Shipping methods" description="Delivery rates and availability rules.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Configuration'],
                ['label' => 'Shipping', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.shipping.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                Add method
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <form method="GET" class="max-w-md">
                            <x-admin.search-input name="search" placeholder="Search methods..." />
                        </form>
                    </x-slot:search>
                </x-admin.table.toolbar>
            </x-slot:toolbar>

            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($methods as $method)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $method->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $method->code }}</td>
                    <td class="px-4 py-3">{{ number_format($method->price / 100, 2) }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$method->is_active ? 'published' : 'archived'">
                            {{ $method->is_active ? 'Active' : 'Inactive' }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.shipping.edit', $method)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No shipping methods yet.</td></tr>
            @endforelse

            @if ($methods->hasPages())
                <x-slot:pagination>{{ $methods->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
