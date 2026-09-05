@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.order').' '.$order->order_number)
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.account.shell
        :customer="$customer"
        :title="__('storefront::storefront.order').' '.$order->order_number"
        :description="__('storefront::storefront.placed').' '.$order->created_at?->format('F j, Y').' · '.($orderStatuses[$order->status] ?? $order->status)"
        section="orders"
    >
        <p>
            <a href="{{ route('storefront.account.orders') }}" class="storefront-link">{{ __('storefront::storefront.back_to_orders') }}</a>
        </p>

        <ol class="storefront-order-timeline">
            @foreach ($timeline as $step)
                <li class="storefront-order-timeline__step storefront-order-timeline__step--{{ $step['state'] }}">
                    <span class="storefront-order-timeline__marker" aria-hidden="true"></span>
                    <span class="storefront-order-timeline__label">{{ $step['label'] }}</span>
                </li>
            @endforeach
        </ol>

        @if ($shipments->isNotEmpty())
            <section class="storefront-account-section">
                <h2 class="storefront-panel__title">{{ __('storefront::storefront.shipment_information') }}</h2>
                <ul class="storefront-stack">
                    @foreach ($shipments as $shipment)
                        <li class="storefront-shipment-card">
                            @if ($shipment->carrier)
                                <p>
                                    <span class="storefront-muted">{{ __('storefront::storefront.carrier') }}</span>
                                    {{ $shipment->carrier }}
                                </p>
                            @endif
                            @if ($shipment->tracking_number)
                                <p>
                                    <span class="storefront-muted">{{ __('storefront::storefront.tracking_number') }}</span>
                                    {{ $shipment->tracking_number }}
                                </p>
                            @endif
                            @if ($shipment->shipped_at)
                                <p>
                                    <span class="storefront-muted">{{ __('storefront::storefront.shipment_date') }}</span>
                                    {{ $shipment->shipped_at->format('F j, Y') }}
                                </p>
                            @endif
                            @if ($shipment->tracking_url)
                                <p>
                                    <a
                                        href="{{ $shipment->tracking_url }}"
                                        class="storefront-btn storefront-btn--secondary"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >{{ __('storefront::storefront.track_shipment') }}</a>
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

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

        <form method="POST" action="{{ route('storefront.account.orders.reorder', $order) }}" class="storefront-account-section">
            @csrf
            <button type="submit" class="storefront-btn">{{ __('storefront::storefront.reorder') }}</button>
        </form>
    </x-storefront.account.shell>
@endsection
