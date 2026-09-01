@extends('layouts.admin')

@section('title', $supplier->name)

@section('page')
    <x-admin.page :title="$supplier->name" description="Supplier overview and purchase order activity">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
                ['label' => 'Suppliers', 'url' => route('admin.inventory.suppliers.index')],
                ['label' => $supplier->name, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            <x-admin.button variant="secondary" :href="route('admin.inventory.suppliers.export', array_merge(['supplier' => $supplier], request()->query()))">Export CSV</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.inventory.suppliers.edit', $supplier)">Edit</x-admin.button>
        </x-slot:actions>

        <x-admin.card class="mb-6">
            <form method="GET" class="grid gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-text" for="range">Date range</label>
                    <select id="range" name="range" class="cf-input mt-1">
                        <option value="7d" @selected($range->preset === '7d')>Last 7 days</option>
                        <option value="30d" @selected($range->preset === '30d')>Last 30 days</option>
                        <option value="90d" @selected($range->preset === '90d')>Last 90 days</option>
                        <option value="all" @selected($range->preset === 'all')>All time</option>
                        <option value="custom" @selected($range->preset === 'custom')>Custom</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text" for="from">From</label>
                    <input id="from" type="date" name="from" value="{{ request('from', $range->from->format('Y-m-d')) }}" class="cf-input mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text" for="to">To</label>
                    <input id="to" type="date" name="to" value="{{ request('to', $range->to->format('Y-m-d')) }}" class="cf-input mt-1">
                </div>
                <div class="flex items-end gap-2">
                    <x-admin.button variant="primary" type="submit">Apply</x-admin.button>
                </div>
            </form>
        </x-admin.card>

        <div class="mb-6 grid gap-4 md:grid-cols-4">
            <x-admin.card title="Total POs">
                <p class="text-2xl font-semibold text-text">{{ $report['total_orders'] }}</p>
            </x-admin.card>
            <x-admin.card title="Open POs">
                <p class="text-2xl font-semibold text-text">{{ $report['open_orders'] }}</p>
            </x-admin.card>
            <x-admin.card title="Incoming units">
                <p class="text-2xl font-semibold text-text">{{ $report['total_incoming'] }}</p>
            </x-admin.card>
            <x-admin.card title="Units received">
                <p class="text-2xl font-semibold text-text">{{ $report['total_received'] }}</p>
            </x-admin.card>
            <x-admin.card title="Value ordered">
                <p class="text-2xl font-semibold text-text">{{ $money->format($report['total_value_ordered'], $report['currency']) }}</p>
            </x-admin.card>
            <x-admin.card title="Value received">
                <p class="text-2xl font-semibold text-text">{{ $money->format($report['total_value_received'], $report['currency']) }}</p>
            </x-admin.card>
        </div>

        @if (count($monthlyOrderSeries) > 0)
            <div class="mb-6">
                <x-admin.bar-chart
                    :series="$monthlyOrderSeries"
                    value-key="count"
                    format="number"
                    title="Purchase orders by month"
                />
            </div>
        @endif

        @if (count($monthlyUnitsSeries) > 0)
            <div class="mb-6">
                <x-admin.dual-bar-chart
                    :series="$monthlyUnitsSeries"
                    title="Units ordered vs received by month"
                />
            </div>
        @endif

        <x-admin.card title="Contact">
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-muted">Email</dt>
                    <dd class="mt-1 text-text">{{ $supplier->email ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Phone</dt>
                    <dd class="mt-1 text-text">{{ $supplier->phone ?: '—' }}</dd>
                </div>
                @if ($supplier->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-muted">Notes</dt>
                        <dd class="mt-1 text-text">{{ $supplier->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-admin.card>

        <x-admin.table.shell class="mt-6">
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Expected</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($report['recent_orders'] as $order)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $order->reference }}</td>
                    <td class="px-4 py-3"><x-admin.badge>{{ $order->status }}</x-admin.badge></td>
                    <td class="px-4 py-3 text-muted">{{ $order->expected_at?->format('Y-m-d') ?: '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.inventory.purchase-orders.show', $order) }}" class="text-sm text-accent hover:underline">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-muted">No purchase orders in this date range.</td></tr>
            @endforelse
        </x-admin.table.shell>
    </x-admin.page>
@endsection
