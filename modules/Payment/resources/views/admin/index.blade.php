@extends('layouts.admin')

@section('title', 'Payments')

@section('page')
    <x-admin.page title="Payments" description="Payment attempts and captured transactions.">
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Sales'],
                ['label' => 'Payments', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-admin.table.shell>
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:filters>
                        <form method="GET">
                            <select name="status" class="cf-input py-2" onchange="this.form.submit()">
                                <option value="">All statuses</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                    </x-slot:filters>
                </x-admin.table.toolbar>
            </x-slot:toolbar>

            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Payment</th>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </x-slot:head>

            @forelse ($payments as $payment)
                @php
                    $statusVariant = match ($payment->status) {
                        'paid' => 'published',
                        'pending' => 'pending',
                        'failed' => 'danger',
                        'refunded' => 'archived',
                        default => 'default',
                    };
                @endphp
                <tr>
                    <td class="px-4 py-3 font-mono text-xs text-text-secondary">{{ Str::limit($payment->uuid, 12) }}</td>
                    <td class="px-4 py-3 text-muted">{{ Str::limit($payment->order_uuid, 12) }}</td>
                    <td class="px-4 py-3">{{ number_format($payment->amount / 100, 2) }} {{ $payment->currency }}</td>
                    <td class="px-4 py-3">
                        <x-admin.badge :variant="$statusVariant">
                            {{ $statuses[$payment->status] ?? $payment->status }}
                        </x-admin.badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <x-admin.button variant="link" :href="route('admin.payments.show', $payment)">View</x-admin.button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No payments yet.</td></tr>
            @endforelse

            @if ($payments->hasPages())
                <x-slot:pagination>{{ $payments->links() }}</x-slot:pagination>
            @endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
