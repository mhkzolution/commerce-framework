@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.addresses'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
    @vite('resources/js/storefront/address.js')
@endpush

@section('content')
    <x-storefront.account.shell
        :customer="$customer"
        :title="__('storefront::storefront.addresses')"
        :description="__('storefront::storefront.addresses_description')"
        section="addresses"
    >
        @if ($addresses->isNotEmpty())
            <ul class="storefront-address-list">
                @foreach ($addresses as $address)
                    <li class="storefront-address-list__item">
                        <div>
                            <p>
                                {{ $address->label ?: __('storefront::storefront.address') }}
                                @if ($address->is_default_shipping)
                                    <span class="storefront-badge">{{ __('storefront::storefront.default_shipping') }}</span>
                                @endif
                                @if ($address->is_default_billing)
                                    <span class="storefront-badge">{{ __('storefront::storefront.default_billing') }}</span>
                                @endif
                            </p>
                            <p class="storefront-muted">{{ ucfirst($address->type) }}</p>
                            <p>
                                {{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif<br>
                                @if ($address->subdistrict || $address->district)
                                    {{ $address->subdistrict }} {{ $address->district }}<br>
                                @endif
                                {{ $address->city }}@if ($address->state), {{ $address->state }}@endif {{ $address->postal_code }}<br>
                                {{ $address->country_code }}
                            </p>
                            <div class="storefront-address-list__actions">
                                <a href="{{ route('storefront.account.addresses.edit', $address) }}" class="storefront-btn storefront-btn--ghost">{{ __('storefront::storefront.edit_address') }}</a>
                                @unless ($address->is_default_shipping)
                                    <form method="POST" action="{{ route('storefront.account.addresses.default-shipping', $address) }}">
                                        @csrf
                                        <button type="submit" class="storefront-btn storefront-btn--ghost">{{ __('storefront::storefront.set_default_shipping') }}</button>
                                    </form>
                                @endunless
                                @unless ($address->is_default_billing)
                                    <form method="POST" action="{{ route('storefront.account.addresses.default-billing', $address) }}">
                                        @csrf
                                        <button type="submit" class="storefront-btn storefront-btn--ghost">{{ __('storefront::storefront.set_default_billing') }}</button>
                                    </form>
                                @endunless
                            </div>
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

        <form method="POST" action="{{ route('storefront.account.addresses.store') }}" class="storefront-stack storefront-account-section">
            @csrf
            <h2 class="storefront-panel__title">{{ __('storefront::storefront.add_address') }}</h2>
            @include('customers::storefront._address_form')
            <button type="submit" class="storefront-btn">{{ __('storefront::storefront.add_address') }}</button>
        </form>
    </x-storefront.account.shell>
@endsection
