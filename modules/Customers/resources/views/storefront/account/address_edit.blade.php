@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.edit_address'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
    @vite('resources/js/storefront/address.js')
@endpush

@section('content')
    <x-storefront.account.shell
        :customer="$customer"
        :title="__('storefront::storefront.edit_address')"
        :description="__('storefront::storefront.addresses_description')"
        section="addresses"
    >
        <form method="POST" action="{{ route('storefront.account.addresses.update', $address) }}" class="storefront-stack">
            @csrf
            @method('PUT')
            @include('customers::storefront._address_form', ['address' => $address])
            <div class="storefront-address-list__actions">
                <button type="submit" class="storefront-btn">{{ __('storefront::storefront.save_address') }}</button>
                <a href="{{ route('storefront.account.addresses') }}" class="storefront-btn storefront-btn--ghost">{{ __('storefront::storefront.cancel') }}</a>
            </div>
        </form>
    </x-storefront.account.shell>
@endsection
