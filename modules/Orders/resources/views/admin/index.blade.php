@extends('layouts.admin')

@section('title', 'Orders')

@section('page')
    <x-admin.page title="Orders" description="Order placement and fulfillment.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Sales'],
                ['label' => 'Orders', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('admin.orders.create')">
                <x-admin.icon name="plus" class="h-4 w-4" />
                New order
            </x-admin.button>
        </x-slot:primaryActions>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <form method="GET" class="max-w-md">
                            <x-admin.search-input name="search" placeholder="Order #, email, name" />
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
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Items</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($orders as $order)
                @php
                    $statusVariant = match ($order->status) {
                        'completed' => 'published',
                        'confirmed' => 'info',
                        'pending' => 'pending',
                        'cancelled' => 'archived',
                        default => 'default',
                    };
                @endphp
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-text">{{ $order->order_number }}</div>
                        <div class="text-xs text-muted">{{ $order->created_at?->format('Y-m-d H:i') }}</div>
                    </td>
                    <td class="px-4 py-3 text-muted">
                        {{ $order->customer_name ?? '—' }}
                        @if ($order->customer_email)
                            <div class="text-xs">{{ $order->customer_email }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $order->lineItems->count() }}</td>
                    <td class="px-4 py-3">{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$statusVariant">
                            {{ $statuses[$order->status] ?? $order->status }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.orders.show', $order)">View</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-muted">No orders yet.</td></tr>
            @endforelse

            @if ($orders->hasPages())
                <x-slot:pagination>{{ $orders->withQueryString()->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
