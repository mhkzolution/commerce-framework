@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.account_shipping'))

@section('content')
    <x-storefront.account.layout
        :customer="$customer"
        active="shipping"
        :title="__('storefront::storefront.account_shipping')"
        :description="__('storefront::storefront.account_shipping_description')"
    >
        @session('status')
            <div class="cf-flash cf-flash--success mb-4">{{ $value }}</div>
        @endsession

        @if ($addresses->isNotEmpty())
            <ul class="space-y-3">
                @foreach ($addresses as $address)
                    <li class="flex items-start justify-between gap-4 rounded-md border border-divider p-4 text-sm">
                        <div>
                            <p class="font-medium text-text">
                                {{ $address->label ?: __('storefront::storefront.address') }}
                                @if ($address->is_default)
                                    <span class="cf-badge cf-badge--default ml-2">{{ __('storefront::storefront.default') }}</span>
                                @endif
                            </p>
                            <p class="mt-1 text-muted">{{ ucfirst($address->type) }}</p>
                            <p class="mt-2 text-text-secondary">
                                {{ $address->line1 }}@if ($address->line2), {{ $address->line2 }}@endif<br>
                                {{ $address->city }}@if ($address->state), {{ $address->state }}@endif {{ $address->postal_code }}<br>
                                {{ $address->country_code }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('storefront.account.addresses.destroy', $address) }}" onsubmit="return confirm(@json(__('storefront::storefront.remove_address_confirm')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-danger hover:underline">{{ __('storefront::storefront.remove') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-muted">{{ __('storefront::storefront.no_addresses_yet') }}</p>
        @endif

        <form method="POST" action="{{ route('storefront.account.addresses.store') }}" class="mt-6 space-y-4 border-t border-border pt-6">
            @csrf
            <h3 class="text-sm font-medium text-text">{{ __('storefront::storefront.add_address') }}</h3>
            @include('customers::admin._address_form')
            <button type="submit" class="cf-btn cf-btn--primary">{{ __('storefront::storefront.add_address') }}</button>
        </form>
    </x-storefront.account.layout>
@endsection
