@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.account_profile'))

@section('content')
    <x-storefront.account.layout
        :customer="$customer"
        active="profile"
        :title="__('storefront::storefront.account_profile')"
        :description="__('storefront::storefront.account_profile_description')"
    >
        @session('status')
            <div class="cf-flash cf-flash--success mb-4">{{ $value }}</div>
        @endsession

        @if ($errors->any())
            <div class="cf-flash cf-flash--danger mb-4">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('storefront.account.profile.update') }}" class="grid gap-4 md:grid-cols-2">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-text">{{ __('storefront::storefront.name') }}</label>
                <input name="name" value="{{ old('name', $customer->name) }}" class="cf-input mt-1" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-text">{{ __('storefront::storefront.email') }}</label>
                <input name="email" type="email" value="{{ old('email', $customer->email) }}" class="cf-input mt-1" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-text">{{ __('storefront::storefront.phone') }}</label>
                <input name="phone" value="{{ old('phone', $customer->phone) }}" class="cf-input mt-1">
            </div>
            <div class="flex items-end">
                <button type="submit" class="cf-btn cf-btn--primary">{{ __('storefront::storefront.update_profile') }}</button>
            </div>
        </form>
    </x-storefront.account.layout>
@endsection
