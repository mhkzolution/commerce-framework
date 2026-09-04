@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.order').' '.$order->order_number)
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.layout.page-container class="storefront-shopper storefront-order">
        <div>
            <a href="{{ route('storefront.account') }}" class="storefront-link">{{ __('storefront::storefront.back_to_account') }}</a>
            <h1 class="storefront-shopper__title">{{ __('storefront::storefront.order') }} {{ $order->order_number }}</h1>
            <p class="storefront-shopper__lede">
                {{ __('storefront::storefront.placed') }} {{ $order->created_at?->format('F j, Y') }}
                · {{ $orderStatuses[$order->status] ?? $order->status }}
            </p>
        </div>

        <div class="storefront-table-wrap">
            <table class="storefront-table">
                <thead>
                    <tr>
                        <th>{{ __('storefront::storefront.item') }}</th>
                        <th class="storefront-table__num">{{ __('storefront::storefront.qty') }}</th>
                        <th class="storefront-table__num">{{ __('storefront::storefront.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->lineItems as $line)
                        <tr>
                            <td>{{ $line->name }}</td>
                            <td class="storefront-table__num">{{ $line->quantity }}</td>
                            <td class="storefront-table__num">{{ number_format($line->line_total / 100, 2) }} {{ $order->currency }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="storefront-table__num">{{ __('storefront::storefront.grand_total') }}</td>
                        <td class="storefront-table__num">{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-storefront.layout.page-container>
@endsection
