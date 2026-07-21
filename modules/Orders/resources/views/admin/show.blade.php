@extends('layouts.admin')

@section('title', $order->order_number)

@section('page')
    @php
        $statusVariant = match ($order->status) {
            'completed' => 'published',
            'confirmed' => 'info',
            'pending' => 'pending',
            'cancelled' => 'archived',
            default => 'default',
        };
    @endphp

    <x-admin.page
        :title="$order->order_number"
        :description="($statuses[$order->status] ?? $order->status).' · '.$order->created_at?->format('Y-m-d H:i')"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Sales'],
                ['label' => 'Orders', 'url' => route('admin.orders.index')],
                ['label' => $order->order_number, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            <x-admin.badge :variant="$statusVariant" class="text-sm">
                {{ $statuses[$order->status] ?? $order->status }}
            </x-admin.badge>
        </x-slot:filters>

        <x-slot:primaryActions>
            @if ($order->status === 'pending')
                <form method="POST" action="{{ route('admin.orders.confirm', $order) }}">
                    @csrf
                    <x-admin.button variant="primary" type="submit">Confirm</x-admin.button>
                </form>
            @endif
            @if ($order->status === 'confirmed')
                <form method="POST" action="{{ route('admin.orders.complete', $order) }}">
                    @csrf
                    <x-admin.button variant="success" type="submit">Complete</x-admin.button>
                </form>
            @endif
        </x-slot:primaryActions>

        <x-slot:secondaryActions>
            @if (in_array($order->status, ['pending', 'confirmed'], true))
                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
                    @csrf
                    <x-admin.button variant="danger" type="submit">Cancel order</x-admin.button>
                </form>
            @endif
            <x-admin.button variant="secondary" :href="route('admin.orders.index')">Back</x-admin.button>
        </x-slot:secondaryActions>

        <div class="grid gap-4 md:grid-cols-3">
            <x-admin.stat-card label="Customer" :value="$order->customer_name ?? 'Guest'">
                <x-slot:footer>
                    @if ($order->customer_email)
                        <div>{{ $order->customer_email }}</div>
                    @endif
                    @if ($order->customer_uuid && Route::has('admin.customers.edit'))
                        <x-admin.button variant="link" :href="route('admin.customers.edit', $order->customer_uuid)" class="mt-1 block !px-0">
                            View customer
                        </x-admin.button>
                    @endif
                </x-slot:footer>
            </x-admin.stat-card>

            <x-admin.stat-card label="Channel" :value="$order->channel" />

            <x-admin.stat-card
                label="Grand total"
                :value="number_format($order->grand_total / 100, 2).' '.$order->currency"
            />
        </div>

        <x-admin.card title="Line items" class="mt-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-muted">
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Unit price</th>
                            <th class="px-4 py-3">Line total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($order->lineItems as $line)
                            <tr>
                                <td class="px-4 py-3 font-medium text-text">{{ $line->name }}</td>
                                <td class="px-4 py-3 text-muted">{{ $line->sku ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $line->quantity }}</td>
                                <td class="px-4 py-3">{{ number_format($line->unit_price / 100, 2) }}</td>
                                <td class="px-4 py-3">{{ number_format($line->line_total / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-border bg-primary-subtle/30">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right text-muted">Subtotal</td>
                            <td class="px-4 py-3">{{ number_format($order->subtotal / 100, 2) }}</td>
                        </tr>
                        @if ($order->discount_total > 0)
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right text-muted">
                                    Discount{{ $order->promotion_code ? ' ('.$order->promotion_code.')' : '' }}
                                </td>
                                <td class="px-4 py-3">-{{ number_format($order->discount_total / 100, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right text-muted">Tax</td>
                            <td class="px-4 py-3">{{ number_format($order->tax_total / 100, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right text-muted">
                                Shipping{{ $order->shipping_method_name ? ' ('.$order->shipping_method_name.')' : '' }}
                            </td>
                            <td class="px-4 py-3">{{ number_format($order->shipping_total / 100, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right font-medium text-text">Grand total</td>
                            <td class="px-4 py-3 font-semibold text-text">
                                {{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-admin.card>

        @if ($order->shipping_address || $order->billing_address)
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @if ($order->shipping_address)
                    <x-admin.card title="Shipping address">
                        <pre class="whitespace-pre-wrap text-sm text-muted">{{ json_encode($order->shipping_address, JSON_PRETTY_PRINT) }}</pre>
                    </x-admin.card>
                @endif
                @if ($order->billing_address)
                    <x-admin.card title="Billing address">
                        <pre class="whitespace-pre-wrap text-sm text-muted">{{ json_encode($order->billing_address, JSON_PRETTY_PRINT) }}</pre>
                    </x-admin.card>
                @endif
            </div>
        @endif
    </x-admin.page>
@endsection
