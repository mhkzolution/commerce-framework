@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page')
    <x-admin.page title="Dashboard" description="Commerce performance overview for the selected period.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[['label' => 'Dashboard', 'active' => true]]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <x-admin.button variant="secondary" :href="route('admin.dashboard.export', request()->query())">
                <x-admin.icon name="arrow-down-tray" class="h-4 w-4" />
                Export CSV
            </x-admin.button>
        </x-slot:secondaryActions>

        <x-slot:filters>
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-wrap gap-2">
                    @foreach (['7d' => '7 days', '30d' => '30 days', '90d' => '90 days'] as $key => $label)
                        <x-admin.button
                            :href="route('admin.dashboard', ['range' => $key])"
                            :variant="$summary['preset'] === $key ? 'primary' : 'secondary'"
                        >{{ $label }}</x-admin.button>
                    @endforeach
                </div>
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="range" value="custom">
                    <label class="text-sm">
                        <span class="mb-1 block text-muted">From</span>
                        <input type="date" name="from" value="{{ $summary['from'] }}" class="cf-input py-2">
                    </label>
                    <label class="text-sm">
                        <span class="mb-1 block text-muted">To</span>
                        <input type="date" name="to" value="{{ $summary['to'] }}" class="cf-input py-2">
                    </label>
                    <x-admin.button type="submit" variant="secondary">Apply</x-admin.button>
                </form>
            </div>
        </x-slot:filters>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card
                label="Revenue (period)"
                :value="number_format($summary['revenue_period'] / 100, 2) . ' ' . $summary['currency']"
                :hint="'All time ' . number_format($summary['revenue_total'] / 100, 2)"
            />
            <x-admin.stat-card
                label="Orders (period)"
                :value="(string) $summary['orders_period']"
                :hint="$summary['orders_total'] . ' total orders'"
            />
            <x-admin.stat-card
                label="Pending orders"
                :value="(string) $summary['orders_pending']"
                hint="Awaiting payment or fulfillment"
            />
            <x-admin.stat-card
                label="Average order value"
                :value="number_format($summary['average_order_value'] / 100, 2) . ' ' . $summary['currency']"
                hint="Paid orders in selected period"
            />
        </div>

        <div class="mt-6">
            <x-admin.bar-chart :series="$revenueSeries" currency="{{ $summary['currency'] }}" title="Daily revenue" />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <x-admin.card title="Orders by status">
                <ul class="space-y-2 text-sm">
                    @forelse ($ordersByStatus as $status => $count)
                        <li class="flex items-center justify-between rounded-md bg-primary-subtle px-3 py-2">
                            <span class="text-text">{{ $orderStatuses[$status] ?? $status }}</span>
                            <x-admin.badge>{{ $count }}</x-admin.badge>
                        </li>
                    @empty
                        <li class="text-muted">No orders in this period.</li>
                    @endforelse
                </ul>
            </x-admin.card>

            <x-admin.table.shell>
                <x-slot:head>
                    <tr class="text-left text-xs uppercase tracking-wide text-muted">
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Total</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </x-slot:head>

                @forelse ($recentOrders as $order)
                    @php
                        $orderBadge = match ($order->status) {
                            'completed' => 'published',
                            'pending' => 'pending',
                            'cancelled' => 'archived',
                            default => 'info',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3">
                            @if (Route::has('admin.orders.show'))
                                <x-admin.button variant="link" :href="route('admin.orders.show', $order)" class="!px-0 font-medium">
                                    {{ $order->order_number }}
                                </x-admin.button>
                            @else
                                <span class="font-medium text-text">{{ $order->order_number }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $order->customer_name ?? $order->customer_email ?? 'Guest' }}</td>
                        <td class="px-4 py-3">{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :variant="$orderBadge">
                                {{ $orderStatuses[$order->status] ?? $order->status }}
                            </x-admin.badge>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-muted">No orders in this period.</td></tr>
                @endforelse
            </x-admin.table.shell>
        </div>
    </x-admin.page>
@endsection
