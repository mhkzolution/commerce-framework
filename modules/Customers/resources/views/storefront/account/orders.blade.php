@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.account_orders'))

@section('content')
    <x-storefront.account.layout
        :customer="$customer"
        active="orders"
        :title="__('storefront::storefront.account_orders')"
        :description="__('storefront::storefront.account_orders_description')"
    >
        @if ($orders !== null)
            <div class="overflow-hidden rounded-lg border border-border">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-text-secondary">{{ __('storefront::storefront.account_orders') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-text-secondary">{{ __('storefront::storefront.order_date') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-text-secondary">{{ __('storefront::storefront.total') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-text-secondary">{{ __('storefront::storefront.order_status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-4 py-3 font-medium text-text">
                                    <a href="{{ route('storefront.account.orders.show', $order) }}" class="hover:underline">{{ $order->order_number }}</a>
                                </td>
                                <td class="px-4 py-3 text-muted">{{ $order->created_at?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">{{ \Commerce\Cart\Support\StorefrontMoney::formatMinor((int) $order->grand_total, (string) $order->currency) }}</td>
                                <td class="px-4 py-3">{{ $orderStatuses[$order->status] ?? $order->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-muted">{{ __('storefront::storefront.no_orders_yet') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-muted">{{ __('storefront::storefront.no_orders_yet') }}</p>
        @endif
    </x-storefront.account.layout>
@endsection
