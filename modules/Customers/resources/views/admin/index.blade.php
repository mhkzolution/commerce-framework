@extends('layouts.admin')

@section('title', 'Customers')

@section('page')
    <x-admin.page title="Customers" description="Buyer profiles for orders.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Sales'],
                ['label' => 'Customers', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.customers.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                New customer
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <form method="GET" class="max-w-md">
                            <x-admin.search-input name="search" placeholder="Search name, email, phone" />
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
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($customers as $customer)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $customer->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $customer->email }}</td>
                    <td class="px-4 py-3 text-muted">{{ $customer->phone ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$customer->status === 'active' ? 'published' : 'archived'">
                            {{ $statuses[$customer->status] ?? $customer->status }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.customers.edit', $customer)">Edit</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No customers yet.</td></tr>
            @endforelse

            @if ($customers->hasPages())
                <x-slot:pagination>{{ $customers->withQueryString()->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
