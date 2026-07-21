@extends('cart::layouts.storefront')

@section('title', 'My account')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-text">My account</h1>
            <p class="mt-1 text-sm text-muted">{{ $customer->name }} · {{ $customer->email }}</p>
        </div>
        <form method="POST" action="{{ route('storefront.account.logout') }}">
            @csrf
            <button type="submit" class="cf-btn cf-btn--secondary">Sign out</button>
        </form>
    </div>

    @session('status')
        <div class="cf-flash cf-flash--success mt-4">{{ $value }}</div>
    @endsession

    @if ($errors->any())
        <div class="cf-flash cf-flash--danger mt-4">{{ $errors->first() }}</div>
    @endif

    <section class="mt-8 rounded-lg border border-border bg-surface p-6 shadow-sm">
        <h2 class="text-lg font-medium text-text">Addresses</h2>

        @if ($addresses->isNotEmpty())
            <ul class="mt-4 space-y-3">
                @foreach ($addresses as $address)
                    <li class="flex items-start justify-between gap-4 rounded-md border border-divider p-4 text-sm">
                        <div>
                            <p class="font-medium text-text">
                                {{ $address->label ?: 'Address' }}
                                @if ($address->is_default)
                                    <span class="cf-badge cf-badge--default ml-2">Default</span>
                                @endif
                            </p>
                            <p class="mt-1 text-muted">{{ ucfirst($address->type) }}</p>
                            <p class="mt-2 text-text-secondary">
                                {{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif<br>
                                {{ $address->city }}@if ($address->state), {{ $address->state }}@endif {{ $address->postal_code }}<br>
                                {{ $address->country_code }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('storefront.account.addresses.destroy', $address) }}" onsubmit="return confirm('Remove this address?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-danger hover:underline">Remove</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="mt-4 text-sm text-muted">No saved addresses yet.</p>
        @endif

        <form method="POST" action="{{ route('storefront.account.addresses.store') }}" class="mt-6 space-y-4 border-t border-border pt-6">
            @csrf
            <h3 class="text-sm font-medium text-text">Add address</h3>
            @include('customers::admin._address_form')
            <button type="submit" class="cf-btn cf-btn--primary">Add address</button>
        </form>
    </section>

    @if ($orders !== null)
        <section class="mt-8 overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
            <div class="border-b border-border px-4 py-3">
                <h2 class="text-lg font-medium text-text">Recent orders</h2>
            </div>
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-surface-muted">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-text-secondary">Order</th>
                        <th class="px-4 py-3 text-left font-medium text-text-secondary">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-text-secondary">Total</th>
                        <th class="px-4 py-3 text-left font-medium text-text-secondary">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-3 font-medium text-text">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-muted">{{ $order->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</td>
                            <td class="px-4 py-3">{{ $orderStatuses[$order->status] ?? $order->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-muted">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endif
@endsection
