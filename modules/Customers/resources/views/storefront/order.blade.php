@extends('cart::layouts.storefront')

@section('title', 'Order ' . $order->order_number)

@section('content')
    <div class="mb-6">
        <a href="{{ route('storefront.account') }}" class="text-sm text-muted hover:underline">← Back to account</a>
        <h1 class="mt-4 text-2xl font-semibold text-text">Order {{ $order->order_number }}</h1>
        <p class="mt-1 text-sm text-muted">
            Placed {{ $order->created_at?->format('F j, Y') }}
            · {{ $orderStatuses[$order->status] ?? $order->status }}
        </p>
    </div>

    <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-surface-muted">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-text-secondary">Item</th>
                    <th class="px-4 py-3 text-right font-medium text-text-secondary">Qty</th>
                    <th class="px-4 py-3 text-right font-medium text-text-secondary">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach ($order->lineItems as $line)
                    <tr>
                        <td class="px-4 py-3 text-text">{{ $line->name }}</td>
                        <td class="px-4 py-3 text-right text-muted">{{ $line->quantity }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($line->line_total / 100, 2) }} {{ $order->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-surface-muted">
                <tr>
                    <td colspan="2" class="px-4 py-3 text-right font-medium text-text">Grand total</td>
                    <td class="px-4 py-3 text-right font-medium text-text">{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection
