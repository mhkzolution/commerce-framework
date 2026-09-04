@php
    $order = $detail->order;
    $lifecycleVariant = match ($order->status) {
        'completed' => 'published',
        'confirmed' => 'info',
        'pending' => 'pending',
        'cancelled' => 'archived',
        default => 'default',
    };
    $financialVariant = match ($detail->financialStatus) {
        'paid' => 'success',
        'partially_paid', 'pending' => 'warning',
        'refunded', 'partially_refunded' => 'archived',
        default => 'pending',
    };
    $fulfillmentVariant = match ($detail->fulfillmentStatus) {
        'fulfilled' => 'published',
        'partial' => 'warning',
        'cancelled' => 'archived',
        default => 'pending',
    };
    $eventLabels = [
        'order.created' => __('orders::admin.event_created'),
        'order.confirmed' => __('orders::admin.event_confirmed'),
        'order.completed' => __('orders::admin.event_completed'),
        'order.cancelled' => __('orders::admin.event_cancelled'),
        'notes.updated' => __('orders::admin.event_notes_updated'),
        'shipment.created' => __('orders::admin.event_shipment_created'),
        'shipment.tracking_updated' => __('orders::admin.event_shipment_tracking_updated'),
        'shipment.cancelled' => __('orders::admin.event_shipment_cancelled'),
    ];
    $movementLabels = [
        'reservation' => __('orders::admin.movement_reservation'),
        'release' => __('orders::admin.movement_release'),
        'sale' => __('orders::admin.movement_sale'),
        'return' => __('orders::admin.movement_return'),
        'adjustment' => __('orders::admin.movement_adjustment'),
        'receive' => __('orders::admin.movement_receive'),
    ];
@endphp

@extends('layouts.admin')

@section('title', $order->order_number)

@section('page')
    <x-admin.page
        :title="$order->order_number"
        :description="($statuses[$order->status] ?? $order->status).' · '.$order->created_at?->format('Y-m-d H:i')"
        :wide="true"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Sales'],
                ['label' => 'Orders', 'url' => route('admin.orders.index')],
                ['label' => $order->order_number, 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            <div class="flex flex-wrap gap-2">
                <x-admin.badge :variant="$lifecycleVariant">{{ $statuses[$order->status] ?? $order->status }}</x-admin.badge>
                <x-admin.badge :variant="$financialVariant" data-financial-status="{{ $detail->financialStatus }}">
                    {{ __('orders::admin.financial_'.$detail->financialStatus) }}
                </x-admin.badge>
                <x-admin.badge :variant="$fulfillmentVariant" data-fulfillment-status="{{ $detail->fulfillmentStatus }}">
                    {{ __('orders::admin.fulfillment_'.$detail->fulfillmentStatus) }}
                </x-admin.badge>
            </div>
        </x-slot:filters>

        <x-slot:primaryActions>
            @if ($detail->canConfirm)
                <form method="POST" action="{{ route('admin.orders.confirm', $order) }}">
                    @csrf
                    <x-admin.button variant="primary" type="submit">{{ __('orders::admin.confirm') }}</x-admin.button>
                </form>
            @endif
            @if ($detail->canComplete)
                <form method="POST" action="{{ route('admin.orders.complete', $order) }}">
                    @csrf
                    <x-admin.button variant="success" type="submit">{{ __('orders::admin.complete') }}</x-admin.button>
                </form>
            @endif
        </x-slot:primaryActions>

        <x-slot:secondaryActions>
            @if ($detail->canCancel)
                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('{{ __('orders::admin.cancel_confirm') }}')">
                    @csrf
                    <x-admin.button variant="danger" type="submit">{{ __('orders::admin.cancel_order') }}</x-admin.button>
                </form>
            @endif
            <x-admin.button variant="secondary" :href="route('admin.orders.index')">{{ __('orders::admin.back') }}</x-admin.button>
        </x-slot:secondaryActions>

        <div class="order-detail" data-order-detail>
            <div class="order-detail-layout">
                <div class="space-y-6">
                    <x-admin.card :title="__('orders::admin.line_items')">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-border text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-muted">
                                        <th class="px-4 py-3">{{ __('orders::admin.products') }}</th>
                                        <th class="px-4 py-3">{{ __('orders::admin.sku') }}</th>
                                        <th class="px-4 py-3">{{ __('orders::admin.quantity') }}</th>
                                        <th class="px-4 py-3">{{ __('orders::admin.fulfilled') }}</th>
                                        <th class="px-4 py-3">{{ __('orders::admin.unit_price') }}</th>
                                        <th class="px-4 py-3">{{ __('orders::admin.line_total') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach ($order->lineItems as $line)
                                        @php
                                            $meta = is_array($line->meta) ? $line->meta : [];
                                            $shipped = $detail->shippedForLine((int) $line->id);
                                            $remaining = $detail->remainingForLine((int) $line->id, (int) $line->quantity);
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-text">
                                                {{ $line->name }}
                                                @if (! empty($meta['price_overridden']))
                                                    <div class="text-xs font-normal text-muted">{{ __('orders::admin.price_overridden') }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-muted">{{ $line->sku ?? '—' }}</td>
                                            <td class="px-4 py-3">{{ $line->quantity }}</td>
                                            <td class="px-4 py-3">{{ $shipped }} / {{ $line->quantity }}</td>
                                            <td class="px-4 py-3">
                                                {{ number_format($line->unit_price / 100, 2) }}
                                                @if (! empty($meta['price_overridden']) && isset($meta['catalog_unit_price']))
                                                    <div class="text-xs text-muted">{{ number_format(((int) $meta['catalog_unit_price']) / 100, 2) }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">{{ number_format($line->line_total / 100, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t border-border bg-primary-subtle/30">
                                    <tr>
                                        <td colspan="5" class="px-4 py-3 text-right text-muted">{{ __('orders::admin.subtotal') }}</td>
                                        <td class="px-4 py-3">{{ number_format($order->subtotal / 100, 2) }}</td>
                                    </tr>
                                    @if ($order->discount_total > 0)
                                        <tr>
                                            <td colspan="5" class="px-4 py-3 text-right text-muted">
                                                {{ __('orders::admin.discount') }}{{ $order->promotion_code ? ' ('.$order->promotion_code.')' : '' }}
                                            </td>
                                            <td class="px-4 py-3">-{{ number_format($order->discount_total / 100, 2) }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td colspan="5" class="px-4 py-3 text-right text-muted">{{ __('orders::admin.tax') }}</td>
                                        <td class="px-4 py-3">{{ number_format($order->tax_total / 100, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="px-4 py-3 text-right text-muted">
                                            {{ __('orders::admin.shipping_fee') }}{{ $order->shipping_method_name ? ' ('.$order->shipping_method_name.')' : '' }}
                                        </td>
                                        <td class="px-4 py-3">{{ number_format($order->shipping_total / 100, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="px-4 py-3 text-right font-medium text-text">{{ __('orders::admin.grand_total') }}</td>
                                        <td class="px-4 py-3 font-semibold text-text">
                                            {{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.fulfillment')">
                        <div class="space-y-6">
                            @forelse ($detail->shipments as $shipment)
                                <div class="rounded-lg border border-border p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="font-medium text-text">
                                                {{ $shipment->carrier ?: __('orders::admin.shipment') }}
                                                @if ($shipment->tracking_number)
                                                    <span class="text-muted">· {{ $shipment->tracking_number }}</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-muted">{{ $shipment->shipped_at?->format('Y-m-d H:i') }} · {{ __('orders::admin.shipment_status_'.$shipment->status) }}</p>
                                        </div>
                                        @if (! $shipment->isCancelled())
                                            <form method="POST" action="{{ route('admin.orders.shipments.cancel', [$order, $shipment]) }}" onsubmit="return confirm('{{ __('orders::admin.cancel_shipment_confirm') }}')">
                                                @csrf
                                                <x-admin.button variant="ghost" type="submit">{{ __('orders::admin.cancel_shipment') }}</x-admin.button>
                                            </form>
                                        @endif
                                    </div>

                                    @if (! $shipment->isCancelled())
                                        <form method="POST" action="{{ route('admin.orders.shipments.tracking', [$order, $shipment]) }}" class="mt-3 grid gap-3 sm:grid-cols-3">
                                            @csrf
                                            <input name="carrier" value="{{ old('carrier', $shipment->carrier) }}" class="cf-input" placeholder="{{ __('orders::admin.carrier') }}">
                                            <input name="tracking_number" value="{{ old('tracking_number', $shipment->tracking_number) }}" class="cf-input" placeholder="{{ __('orders::admin.tracking_number') }}">
                                            <x-admin.button variant="secondary" type="submit">{{ __('orders::admin.save_tracking') }}</x-admin.button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-muted">{{ __('orders::admin.no_shipments') }}</p>
                            @endforelse

                            @if ($detail->canFulfill)
                                <form method="POST" action="{{ route('admin.orders.shipments.store', $order) }}" class="space-y-4 border-t border-border pt-4">
                                    @csrf
                                    <p class="text-sm font-medium text-text">{{ __('orders::admin.create_shipment') }}</p>
                                    @foreach ($order->lineItems as $line)
                                        @php $remaining = $detail->remainingForLine((int) $line->id, (int) $line->quantity); @endphp
                                        @if ($remaining > 0)
                                            <div class="flex items-center justify-between gap-4 text-sm">
                                                <span>{{ $line->name }} <span class="text-muted">({{ $remaining }} {{ __('orders::admin.remaining') }})</span></span>
                                                <input type="number" min="0" max="{{ $remaining }}" name="items[{{ $line->uuid }}][quantity]" value="{{ $remaining }}" class="cf-input w-24">
                                            </div>
                                        @endif
                                    @endforeach
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <input name="carrier" value="{{ old('carrier') }}" class="cf-input" placeholder="{{ __('orders::admin.carrier') }}">
                                        <input name="tracking_number" value="{{ old('tracking_number') }}" class="cf-input" placeholder="{{ __('orders::admin.tracking_number') }}">
                                    </div>
                                    <x-admin.button variant="primary" type="submit">{{ __('orders::admin.fulfill_items') }}</x-admin.button>
                                </form>
                            @endif
                        </div>
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.payments')">
                        @if ($detail->payments->isEmpty())
                            <p class="text-sm text-muted">{{ __('orders::admin.no_payments') }}</p>
                        @else
                            <table class="min-w-full divide-y divide-border text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-muted">
                                        <th class="px-4 py-3">{{ __('orders::admin.status') }}</th>
                                        <th class="px-4 py-3">{{ __('orders::admin.amount') }}</th>
                                        <th class="px-4 py-3">{{ __('orders::admin.gateway_reference') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    @foreach ($detail->payments as $payment)
                                        <tr>
                                            <td class="px-4 py-3">{{ $payment->status }}</td>
                                            <td class="px-4 py-3">{{ number_format($payment->amount / 100, 2) }} {{ $payment->currency }}</td>
                                            <td class="px-4 py-3">{{ $payment->gateway_reference ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.timeline')">
                        <ol class="order-detail-timeline" data-order-timeline>
                            @forelse ($detail->timeline as $event)
                                <li>
                                    <p class="font-medium text-text">{{ $eventLabels[$event->type] ?? $event->message }}</p>
                                    <p class="text-xs text-muted">{{ $event->created_at?->format('Y-m-d H:i') }}</p>
                                </li>
                            @empty
                                <li class="text-sm text-muted">{{ __('orders::admin.timeline_empty') }}</li>
                            @endforelse
                        </ol>
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.inventory_history')">
                        <div data-inventory-history>
                            @if ($detail->stockMovements->isEmpty())
                                <p class="text-sm text-muted">{{ __('orders::admin.inventory_empty') }}</p>
                            @else
                                <table class="min-w-full divide-y divide-border text-sm">
                                    <thead>
                                        <tr class="text-left text-xs uppercase tracking-wide text-muted">
                                            <th class="px-4 py-3">{{ __('orders::admin.movement_type') }}</th>
                                            <th class="px-4 py-3">{{ __('orders::admin.quantity') }}</th>
                                            <th class="px-4 py-3">{{ __('orders::admin.reason') }}</th>
                                            <th class="px-4 py-3">{{ __('orders::admin.when') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border">
                                        @foreach ($detail->stockMovements as $movement)
                                            <tr>
                                                <td class="px-4 py-3">{{ $movementLabels[$movement->type] ?? $movement->type }}</td>
                                                <td class="px-4 py-3">{{ $movement->quantity }}</td>
                                                <td class="px-4 py-3">{{ $movement->reason }}</td>
                                                <td class="px-4 py-3 text-muted">{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </x-admin.card>
                </div>

                <aside class="space-y-6">
                    <x-admin.card :title="__('orders::admin.actions')">
                        <div class="space-y-2" data-action-panel>
                            @if ($detail->canConfirm)
                                <form method="POST" action="{{ route('admin.orders.confirm', $order) }}">
                                    @csrf
                                    <x-admin.button variant="primary" type="submit" class="w-full">{{ __('orders::admin.confirm') }}</x-admin.button>
                                </form>
                            @endif
                            @if ($detail->canFulfill)
                                <p class="text-sm text-muted">{{ __('orders::admin.fulfill_hint') }}</p>
                            @endif
                            @if ($detail->canComplete)
                                <form method="POST" action="{{ route('admin.orders.complete', $order) }}">
                                    @csrf
                                    <x-admin.button variant="success" type="submit" class="w-full">{{ __('orders::admin.complete') }}</x-admin.button>
                                </form>
                            @endif
                            @if ($detail->canCancel)
                                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('{{ __('orders::admin.cancel_confirm') }}')">
                                    @csrf
                                    <x-admin.button variant="danger" type="submit" class="w-full">{{ __('orders::admin.cancel_order') }}</x-admin.button>
                                </form>
                            @endif
                            @if (! $detail->canConfirm && ! $detail->canComplete && ! $detail->canCancel && ! $detail->canFulfill)
                                <p class="text-sm text-muted">{{ __('orders::admin.no_actions') }}</p>
                            @endif
                        </div>
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.customer')">
                        <div class="space-y-2 text-sm" data-customer-summary>
                            <p class="font-medium text-text">{{ $order->customer_name ?: __('orders::admin.guest') }}</p>
                            @if ($order->customer_email)
                                <p>{{ $order->customer_email }}</p>
                            @endif
                            @if (! empty($order->meta['customer_phone']))
                                <p>{{ $order->meta['customer_phone'] }}</p>
                            @endif
                            @if ($order->customer_uuid && Route::has('admin.customers.edit'))
                                <x-admin.button variant="link" :href="route('admin.customers.edit', $order->customer_uuid)" class="!px-0">
                                    {{ __('orders::admin.view_customer') }}
                                </x-admin.button>
                            @endif
                        </div>
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.shipping')">
                        @if ($detail->shippingLines === [])
                            <p class="text-sm text-muted">{{ __('orders::admin.no_address') }}</p>
                        @else
                            <p class="whitespace-pre-line text-sm">{{ implode("\n", $detail->shippingLines) }}</p>
                        @endif
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.billing')">
                        @if ($detail->billingLines === [])
                            <p class="text-sm text-muted">{{ __('orders::admin.no_address') }}</p>
                        @else
                            <p class="whitespace-pre-line text-sm">{{ implode("\n", $detail->billingLines) }}</p>
                        @endif
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.notes')">
                        <form method="POST" action="{{ route('admin.orders.notes.update', $order) }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-text" for="internal_notes">{{ __('orders::admin.internal_notes') }}</label>
                                <textarea id="internal_notes" name="internal_notes" rows="3" class="cf-input mt-1" @disabled(! $detail->canEditNotes)>{{ old('internal_notes', $order->meta['notes'] ?? '') }}</textarea>
                                <p class="mt-1 text-xs text-muted">{{ __('orders::admin.notes_hint') }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-text" for="customer_note">{{ __('orders::admin.customer_note') }}</label>
                                <textarea id="customer_note" name="customer_note" rows="3" class="cf-input mt-1" @disabled(! $detail->canEditNotes)>{{ old('customer_note', $order->meta['customer_note'] ?? '') }}</textarea>
                            </div>
                            @if ($detail->canEditNotes)
                                <x-admin.button variant="secondary" type="submit">{{ __('orders::admin.save_notes') }}</x-admin.button>
                            @endif
                        </form>
                    </x-admin.card>

                    <x-admin.card :title="__('orders::admin.audit')">
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-muted">{{ __('orders::admin.channel') }}</dt>
                                <dd>{{ config('orders.channels.'.$order->channel, $order->channel) }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-muted">{{ __('orders::admin.created_by') }}</dt>
                                <dd>{{ $detail->createdBy->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-muted">{{ __('orders::admin.updated_by') }}</dt>
                                <dd>{{ $detail->updatedBy->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-muted">{{ __('orders::admin.created_at') }}</dt>
                                <dd>{{ $order->created_at?->format('Y-m-d H:i') }}</dd>
                            </div>
                        </dl>
                    </x-admin.card>
                </aside>
            </div>
        </div>
    </x-admin.page>
@endsection
