@extends('layouts.admin')

@section('title', 'Payment')

@section('page')
    @php
        $statusVariant = match ($payment->status) {
            'paid' => 'published',
            'pending' => 'pending',
            'failed' => 'danger',
            'refunded' => 'archived',
            default => 'default',
        };
    @endphp

    <x-admin.page title="Payment" :description="Str::limit($payment->uuid, 36)">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Sales'],
                ['label' => 'Payments', 'url' => route('admin.payments.index')],
                ['label' => Str::limit($payment->uuid, 12), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:filters>
            <x-admin.badge :variant="$statusVariant">
                {{ $statuses[$payment->status] ?? $payment->status }}
            </x-admin.badge>
        </x-slot:filters>

        <x-slot:secondaryActions>
            <x-admin.button variant="secondary" :href="route('admin.payments.index')">Back</x-admin.button>
        </x-slot:secondaryActions>

        <div class="grid gap-4 md:grid-cols-3">
            <x-admin.stat-card
                label="Amount"
                :value="number_format($payment->amount / 100, 2).' '.$payment->currency"
            />
            <x-admin.stat-card
                label="Status"
                :value="$statuses[$payment->status] ?? $payment->status"
            />
            <x-admin.stat-card label="Method" :value="$payment->method" />
        </div>

        @if ($order)
            <x-admin.card title="Order" class="mt-6">
                <p class="text-sm text-text-secondary">
                    {{ $order->order_number }} · {{ $order->customer_name ?? 'Guest' }}
                </p>
                <p class="mt-1 text-sm text-muted">
                    Order status: {{ config('orders.statuses')[$order->status] ?? $order->status }}
                </p>
                @if (Route::has('admin.orders.show'))
                    <x-admin.button variant="link" :href="route('admin.orders.show', $order)" class="mt-2 block !px-0">
                        View order
                    </x-admin.button>
                @endif
            </x-admin.card>
        @endif

        @if ($payment->gateway_reference)
            <p class="mt-4 text-sm text-muted">Reference: {{ $payment->gateway_reference }}</p>
        @endif
    </x-admin.page>
@endsection
