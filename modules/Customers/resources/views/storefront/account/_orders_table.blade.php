@php
    $formatMoney = static fn ($order): string => number_format(((int) $order->grand_total) / 100, 2).' '.$order->currency;
@endphp

<div class="storefront-table-wrap">
    <table class="storefront-table">
        <thead>
            <tr>
                <th>{{ __('storefront::storefront.order') }}</th>
                <th>{{ __('storefront::storefront.date') }}</th>
                <th>{{ __('storefront::storefront.total') }}</th>
                <th>{{ __('storefront::storefront.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>
                        <a href="{{ route('storefront.account.orders.show', $order) }}" class="storefront-link">{{ $order->order_number }}</a>
                    </td>
                    <td class="storefront-muted">{{ $order->created_at?->format('Y-m-d') }}</td>
                    <td>{{ $formatMoney($order) }}</td>
                    <td>{{ $orderStatuses[$order->status] ?? $order->status }}</td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('storefront::storefront.no_orders') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
