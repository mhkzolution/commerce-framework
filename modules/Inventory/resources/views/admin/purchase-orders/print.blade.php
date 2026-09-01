<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $order->reference }} — Purchase Order</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; color: #111; margin: 2rem; }
        h1 { margin: 0 0 0.25rem; font-size: 1.5rem; }
        .meta { color: #555; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #ddd; padding: 0.5rem 0.75rem; text-align: left; }
        th { font-size: 0.75rem; text-transform: uppercase; color: #666; }
        .actions { margin-bottom: 1rem; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route('admin.inventory.purchase-orders.pdf', $order) }}">Download PDF</a>
    </div>

    <h1>{{ $order->reference }}</h1>
    <p class="meta">
        Supplier: {{ $order->supplier?->name ?? $order->supplier_name ?: '—' }} ·
        Status: {{ ucfirst($order->status) }} ·
        Expected: {{ $order->expected_at?->format('M j, Y') ?: '—' }}
    </p>

    @if ($order->notes)
        <p class="meta">Notes: {{ $order->notes }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Unit cost</th>
                <th>Ordered</th>
                <th>Received</th>
                <th>Incoming</th>
                <th>Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->lines as $line)
                @php
                    $context = $variantContext[$line->purchasable_uuid] ?? ['variant' => null, 'product_name' => null];
                @endphp
                <tr>
                    <td>{{ $context['product_name'] ?? '—' }}</td>
                    <td>{{ $line->sku }}</td>
                    <td>{{ $line->unit_cost !== null ? $money->format((float) $line->unit_cost, $order->currency) : '—' }}</td>
                    <td>{{ $line->quantity_ordered }}</td>
                    <td>{{ $line->quantity_received }}</td>
                    <td>{{ $line->incomingQuantity() }}</td>
                    <td>{{ $line->unit_cost !== null ? $money->format($line->orderedValue(), $order->currency) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
