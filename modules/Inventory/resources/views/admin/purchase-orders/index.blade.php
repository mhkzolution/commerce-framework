@extends('layouts.admin')

@section('title', 'Purchase Orders')

@section('page')
    <x-admin.page title="Purchase orders" description="Track incoming stock from suppliers">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
                ['label' => 'Purchase orders', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:actions>
            @if ($canManagePurchaseOrders ?? false)
                <x-admin.button variant="primary" :href="route('admin.inventory.purchase-orders.create')">Create PO</x-admin.button>
            @endif
            <x-admin.button variant="secondary" :href="route('admin.inventory.purchase-orders.export', request()->query())">Export CSV</x-admin.button>
            <x-admin.button variant="secondary" :href="route('admin.inventory.purchase-orders.analytics')">Analytics</x-admin.button>
            @if (($failedEmailCount ?? 0) > 0)
                <x-admin.button variant="secondary" :href="route('admin.inventory.purchase-orders.failed-jobs.index')">
                    Failed emails ({{ $failedEmailCount }})
                </x-admin.button>
            @endif
        </x-slot:actions>

        <x-admin.card class="mb-6">
            <form method="GET" class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-text" for="supplier_id">Supplier</label>
                    <select id="supplier_id" name="supplier_id" class="cf-input mt-1">
                        <option value="">All suppliers</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) ($filterSupplierId ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text" for="status">Status</label>
                    <select id="status" name="status" class="cf-input mt-1">
                        <option value="">All statuses</option>
                        @foreach (['ordered', 'partial', 'received', 'cancelled'] as $statusOption)
                            <option value="{{ $statusOption }}" @selected(($filterStatus ?? '') === $statusOption)>{{ ucfirst($statusOption) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <x-admin.button variant="primary" type="submit">Filter</x-admin.button>
                    <x-admin.button variant="secondary" :href="route('admin.inventory.purchase-orders.index')">Clear</x-admin.button>
                </div>
            </form>
        </x-admin.card>

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Supplier</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Lines</th>
                    <th class="px-4 py-3">Expected</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($orders as $order)
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $order->reference }}</td>
                    <td class="px-4 py-3 text-muted">{{ $order->supplier?->name ?? $order->supplier_name ?: '—' }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge>{{ $order->status }}</x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-muted">{{ $order->lines_count }}</td>
                    <td class="px-4 py-3 text-muted">{{ $order->expected_at?->format('Y-m-d') ?: '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.inventory.purchase-orders.show', $order) }}" class="text-sm text-accent hover:underline">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-muted">No purchase orders yet.</td></tr>
            @endforelse

            @if ($orders->hasPages())
                <x-slot:pagination>{{ $orders->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
