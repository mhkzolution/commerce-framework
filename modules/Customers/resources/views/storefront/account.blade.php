@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.my_account'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.layout.page-container class="storefront-shopper storefront-account">
        <div class="storefront-shopper__header">
            <div>
                <h1 class="storefront-shopper__title">{{ __('storefront::storefront.my_account') }}</h1>
                <p class="storefront-shopper__lede">{{ $customer->name }} · {{ $customer->email }}</p>
            </div>
            <form method="POST" action="{{ route('storefront.account.logout') }}">
                @csrf
                <button type="submit" class="storefront-btn storefront-btn--secondary">{{ __('storefront::storefront.sign_out') }}</button>
            </form>
        </div>

        @session('status')
            <div class="storefront-flash storefront-flash--success">{{ $value }}</div>
        @endsession

        @if ($errors->any())
            <div class="storefront-flash storefront-flash--danger">{{ $errors->first() }}</div>
        @endif

        <section class="storefront-panel storefront-stack">
            <h2 class="storefront-panel__title">{{ __('storefront::storefront.profile') }}</h2>
            <form method="POST" action="{{ route('storefront.account.profile.update') }}" class="storefront-form-grid">
                @csrf
                @method('PUT')
                <div class="storefront-field">
                    <label class="storefront-field__label">{{ __('storefront::storefront.name') }}</label>
                    <input name="name" value="{{ old('name', $customer->name) }}" class="storefront-input" required>
                </div>
                <div class="storefront-field">
                    <label class="storefront-field__label">{{ __('storefront::storefront.email') }}</label>
                    <input name="email" type="email" value="{{ old('email', $customer->email) }}" class="storefront-input" required>
                </div>
                <div class="storefront-field">
                    <label class="storefront-field__label">{{ __('storefront::storefront.phone') }}</label>
                    <input name="phone" value="{{ old('phone', $customer->phone) }}" class="storefront-input">
                </div>
                <div class="storefront-field">
                    <button type="submit" class="storefront-btn">{{ __('storefront::storefront.update_profile') }}</button>
                </div>
            </form>
        </section>

        <section class="storefront-panel storefront-stack">
            <h2 class="storefront-panel__title">{{ __('storefront::storefront.addresses') }}</h2>

            @if ($addresses->isNotEmpty())
                <ul class="storefront-address-list">
                    @foreach ($addresses as $address)
                        <li class="storefront-address-list__item">
                            <div>
                                <p>
                                    {{ $address->label ?: __('storefront::storefront.address') }}
                                    @if ($address->is_default)
                                        <span class="storefront-badge">{{ __('storefront::storefront.default') }}</span>
                                    @endif
                                </p>
                                <p class="storefront-muted">{{ ucfirst($address->type) }}</p>
                                <p>
                                    {{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif<br>
                                    {{ $address->city }}@if ($address->state), {{ $address->state }}@endif {{ $address->postal_code }}<br>
                                    {{ $address->country_code }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('storefront.account.addresses.destroy', $address) }}" onsubmit="return confirm(@json(__('storefront::storefront.confirm_remove_address')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="storefront-btn storefront-btn--danger">{{ __('storefront::storefront.remove_address') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="storefront-muted">{{ __('storefront::storefront.no_addresses') }}</p>
            @endif

            <form method="POST" action="{{ route('storefront.account.addresses.store') }}" class="storefront-stack">
                @csrf
                <h3 class="storefront-field__label">{{ __('storefront::storefront.add_address') }}</h3>
                @include('customers::storefront._address_form')
                <button type="submit" class="storefront-btn">{{ __('storefront::storefront.add_address') }}</button>
            </form>
        </section>

        @if ($orders !== null)
            <section class="storefront-stack">
                <h2 class="storefront-panel__title">{{ __('storefront::storefront.recent_orders') }}</h2>
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
                                    <td>{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}</td>
                                    <td>{{ $orderStatuses[$order->status] ?? $order->status }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">{{ __('storefront::storefront.no_orders') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </x-storefront.layout.page-container>
@endsection
