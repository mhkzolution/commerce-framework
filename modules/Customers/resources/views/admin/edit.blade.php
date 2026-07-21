@extends('layouts.admin')

@section('title', $customer->name)

@section('page')
    <x-admin.page :title="$customer->name" :description="$customer->email">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Sales'],
                ['label' => 'Customers', 'url' => route('admin.customers.index')],
                ['label' => $customer->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')">
                @csrf
                @method('DELETE')
                <x-admin.button variant="danger" type="submit">Delete</x-admin.button>
            </form>
        </x-slot:secondaryActions>

        <x-admin.form.shell action="{{ route('admin.customers.update', $customer) }}" method="POST" class="max-w-2xl">
            @csrf
            @method('PUT')
            <x-admin.form.section title="Customer details">
                @include('customers::admin._form', ['customer' => $customer, 'statuses' => $statuses])
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" :href="route('admin.customers.index')">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save changes</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>

        <x-admin.card title="Addresses" class="mt-6 max-w-2xl">
            @if ($addresses->isNotEmpty())
                <ul class="space-y-3">
                    @foreach ($addresses as $address)
                        <li class="flex items-start justify-between gap-4 rounded-md border border-border p-4 text-sm">
                            <div>
                                <p class="font-medium text-text">
                                    {{ $address->label ?: 'Address' }}
                                    @if ($address->is_default)
                                        <x-admin.badge variant="info" class="ml-2">Default</x-admin.badge>
                                    @endif
                                </p>
                                <p class="mt-1 text-muted">{{ ucfirst($address->type) }}</p>
                                <p class="mt-2 text-text-secondary">
                                    {{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif<br>
                                    {{ $address->city }}@if ($address->state), {{ $address->state }}@endif {{ $address->postal_code }}<br>
                                    {{ $address->country_code }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('admin.customers.addresses.destroy', [$customer, $address]) }}" onsubmit="return confirm('Remove this address?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-danger hover:underline">Remove</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-muted">No addresses yet.</p>
            @endif

            <form method="POST" action="{{ route('admin.customers.addresses.store', $customer) }}" class="mt-6 space-y-4 border-t border-border pt-6">
                @csrf
                <h3 class="text-sm font-medium text-text">Add address</h3>
                @include('customers::admin._address_form')
                <x-admin.button variant="primary" type="submit">Add address</x-admin.button>
            </form>
        </x-admin.card>

        @if ($orders !== null)
            <div class="mt-6">
                <h2 class="mb-4 text-lg font-semibold text-text">Order history</h2>
                <x-admin.table.shell>
                <x-slot:head>
                    <tr class="text-left text-xs uppercase tracking-wide text-muted">
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </x-slot:head>

                @forelse ($orders as $order)
                    @php
                        $orderBadge = match ($order->status) {
                            'completed' => 'published',
                            'pending' => 'pending',
                            'cancelled' => 'archived',
                            default => 'info',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium text-text">{{ $order->order_number }}</td>
                        <td class="px-4 py-3 text-muted">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :variant="$orderBadge">
                                {{ $orderStatuses[$order->status] ?? $order->status }}
                            </x-admin.badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (Route::has('admin.orders.show'))
                                <x-admin.button variant="link" :href="route('admin.orders.show', $order)">View</x-admin.button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No orders yet.</td></tr>
                @endforelse

                @if ($orders->hasPages())
                    <x-slot:pagination>{{ $orders->links() }}</x-slot:pagination>
                @endif
                </x-admin.table.shell>
            </div>
        @endif
    </x-admin.page>
@endsection
