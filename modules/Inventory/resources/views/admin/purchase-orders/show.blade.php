@extends('layouts.admin')

@section('title', $order->reference)

@section('page')
    <x-admin.page :title="$order->reference" description="Receive incoming stock against this purchase order">
        <x-slot:actions>
            @if (($canManagePurchaseOrders ?? false) && $order->isOpen())
                <form method="POST" action="{{ route('admin.inventory.purchase-orders.cancel', $order) }}" onsubmit="return confirm('Cancel this purchase order?')">
                    @csrf
                    <x-admin.button variant="secondary" type="submit">Cancel PO</x-admin.button>
                </form>
            @endif
            <x-admin.button variant="secondary" :href="route('admin.inventory.purchase-orders.print', $order)" target="_blank">Print / PDF</x-admin.button>
        </x-slot:actions>

        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Inventory', 'url' => route('admin.inventory.index')],
                ['label' => 'Purchase orders', 'url' => route('admin.inventory.purchase-orders.index')],
                ['label' => $order->reference, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <div class="mb-6 grid gap-4 md:grid-cols-4">
            <x-admin.card title="Status">
                <p class="text-lg font-medium text-text">{{ ucfirst($order->status) }}</p>
            </x-admin.card>
            <x-admin.card title="Supplier">
                <p class="text-text">{{ $order->supplier?->name ?? $order->supplier_name ?: '—' }}</p>
                @if ($order->supplier?->email)
                    <p class="mt-1 text-sm text-muted">{{ $order->supplier->email }}</p>
                @endif
            </x-admin.card>
            <x-admin.card title="Expected">
                <p class="text-text">{{ $order->expected_at?->format('M j, Y') ?: '—' }}</p>
            </x-admin.card>
            <x-admin.card title="Order value">
                <p class="text-text">{{ $money->format($orderValueOrdered, $order->currency) }}</p>
                <p class="mt-1 text-sm text-muted">Received {{ $money->format($orderValueReceived, $order->currency) }}</p>
            </x-admin.card>
        </div>

        @if ($canManagePurchaseOrders ?? false)
            <x-admin.card title="Email to supplier" class="mb-6">
                <form method="POST" action="{{ route('admin.inventory.purchase-orders.email', $order) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="min-w-[16rem] flex-1">
                        <label class="block text-sm font-medium text-text" for="email">Recipient email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $order->supplier?->email) }}" class="cf-input mt-1" placeholder="supplier@example.com">
                    </div>
                    <x-admin.button variant="secondary" type="submit">Send PDF</x-admin.button>
                </form>
            </x-admin.card>
        @endif

        @if ($order->notes)
            <x-admin.card title="Notes" class="mb-6">
                <p class="text-sm text-muted">{{ $order->notes }}</p>
            </x-admin.card>
        @endif

        <x-admin.table.shell>
            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Unit cost</th>
                    <th class="px-4 py-3">Ordered</th>
                    <th class="px-4 py-3">Received</th>
                    <th class="px-4 py-3">Incoming</th>
                    <th class="px-4 py-3 text-right">Receive</th>
                </tr>
            </x-slot:head>

            @foreach ($order->lines as $line)
                @php
                    $context = $variantContext[$line->purchasable_uuid] ?? ['variant' => null, 'product_name' => null];
                @endphp
                <tr>
                    <td class="px-4 py-3 font-medium text-text">{{ $context['product_name'] ?? '—' }}</td>
                    <td class="px-4 py-3 text-muted">{{ $line->sku }}</td>
                    <td class="px-4 py-3 text-muted">{{ $line->unit_cost !== null ? $money->format((float) $line->unit_cost, $order->currency) : '—' }}</td>
                    <td class="px-4 py-3">{{ $line->quantity_ordered }}</td>
                    <td class="px-4 py-3">{{ $line->quantity_received }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$line->incomingQuantity() > 0 ? 'warning' : 'published'">
                            {{ $line->incomingQuantity() }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if ($line->incomingQuantity() > 0 && $order->isOpen() && ($canManagePurchaseOrders ?? false))
                            <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                <form method="POST" action="{{ route('admin.inventory.purchase-orders.lines.receive', [$order, $line]) }}" class="inline-flex items-center gap-2">
                                    @csrf
                                    <input type="number" name="quantity" min="1" max="{{ $line->incomingQuantity() }}" value="{{ $line->incomingQuantity() }}" class="cf-input w-20 py-1 text-sm">
                                    <x-admin.button variant="secondary" type="submit">Receive</x-admin.button>
                                </form>
                                <form method="POST" action="{{ route('admin.inventory.purchase-orders.lines.cancel', [$order, $line]) }}" class="inline" onsubmit="return confirm('Cancel remaining quantity on this line?')">
                                    @csrf
                                    <x-admin.button variant="secondary" type="submit">Cancel line</x-admin.button>
                                </form>
                            </div>
                        @else
                            <span class="text-sm text-muted">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-admin.table.shell>
    </x-admin.page>
@endsection
