@extends('layouts.admin')

@section('title', 'PO Analytics')

@section('page')
    <x-admin.page title="Purchase order analytics" description="Overview of purchase order activity across all suppliers.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
                ['label' => 'Purchase orders', 'url' => route('admin.inventory.purchase-orders.index')],
                ['label' => 'Analytics', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <x-admin.button variant="secondary" :href="route('admin.inventory.purchase-orders.analytics.export', request()->query())">
                Export CSV
            </x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.inventory.purchase-orders.index')">
                All purchase orders
            </x-admin.button>
        </x-slot:secondaryActions>

        <x-admin.card class="mb-6">
            <form method="GET" class="grid gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-text" for="range">Date range</label>
                    <select id="range" name="range" class="cf-input mt-1">
                        <option value="7d" @selected($summary['preset'] === '7d')>Last 7 days</option>
                        <option value="30d" @selected($summary['preset'] === '30d')>Last 30 days</option>
                        <option value="90d" @selected($summary['preset'] === '90d')>Last 90 days</option>
                        <option value="all" @selected($summary['preset'] === 'all')>All time</option>
                        <option value="custom" @selected($summary['preset'] === 'custom')>Custom</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text" for="from">From</label>
                    <input id="from" type="date" name="from" value="{{ $summary['from'] }}" class="cf-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text" for="to">To</label>
                    <input id="to" type="date" name="to" value="{{ $summary['to'] }}" class="cf-input mt-1">
                </div>
                <div class="flex items-end gap-2">
                    <x-admin.button variant="primary" type="submit">Apply</x-admin.button>
                </div>
            </form>
        </x-admin.card>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-admin.stat-card label="Total POs" :value="(string) $summary['total_orders']" hint="In selected period" />
            <x-admin.stat-card label="Open POs" :value="(string) $summary['open_orders']" hint="Ordered or partially received" />
            <x-admin.stat-card label="Received POs" :value="(string) $summary['received_orders']" hint="Fully received" />
            <x-admin.stat-card label="Cancelled POs" :value="(string) $summary['cancelled_orders']" hint="Cancelled in period" />
            <x-admin.stat-card label="Units ordered" :value="(string) $summary['total_units_ordered']" hint="Line quantities ordered" />
            <x-admin.stat-card label="Units received" :value="(string) $summary['total_units_received']" hint="Line quantities received" />
            <x-admin.stat-card label="Value ordered" :value="$money->format($summary['total_value_ordered'], $summary['currency'])" hint="Converted to base currency" />
            <x-admin.stat-card label="Value received" :value="$money->format($summary['total_value_received'], $summary['currency'])" hint="Converted to base currency" />
        </div>

        <div class="mt-6">
            <x-admin.bar-chart
                :series="$ordersSeries"
                value-key="count"
                format="number"
                title="Daily purchase orders"
            />
        </div>

        @if (count($unitsSeries) > 0)
            <div class="mt-6">
                <x-admin.dual-bar-chart
                    :series="$unitsSeries"
                    title="Units ordered vs received by month"
                />
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <x-admin.card title="Orders by status">
                <ul class="space-y-2 text-sm">
                    @forelse ($ordersByStatus as $status => $count)
                        <li class="flex items-center justify-between rounded-md bg-primary-subtle px-3 py-2">
                            <span class="text-text">{{ ucfirst($status) }}</span>
                            <x-admin.badge>{{ $count }}</x-admin.badge>
                        </li>
                    @empty
                        <li class="text-muted">No purchase orders in this period.</li>
                    @endforelse
                </ul>
            </x-admin.card>

            <x-admin.card title="Top suppliers">
                <ul class="space-y-2 text-sm">
                    @forelse ($topSuppliers as $row)
                        <li class="flex items-center justify-between rounded-md bg-primary-subtle px-3 py-2">
                            <span class="text-text">{{ $row->supplier_name }}</span>
                            <x-admin.badge>{{ $row->total }}</x-admin.badge>
                        </li>
                    @empty
                        <li class="text-muted">No supplier activity in this period.</li>
                    @endforelse
                </ul>
            </x-admin.card>
        </div>
    </x-admin.page>
@endsection
