@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.profile'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.account.shell
        :customer="$customer"
        :title="__('storefront::storefront.profile')"
        :description="__('storefront::storefront.profile_description')"
        section="profile"
    >
        <form method="POST" action="{{ route('storefront.account.profile.update') }}" class="storefront-form-grid">
            @csrf
            @method('PUT')
            <div class="storefront-field">
                <label class="storefront-field__label" for="profile-name">{{ __('storefront::storefront.name') }}</label>
                <input id="profile-name" name="name" value="{{ old('name', $customer->name) }}" class="storefront-input" required>
            </div>
            <div class="storefront-field">
                <label class="storefront-field__label" for="profile-email">{{ __('storefront::storefront.email') }}</label>
                <input id="profile-email" name="email" type="email" value="{{ old('email', $customer->email) }}" class="storefront-input" required>
            </div>
            <div class="storefront-field">
                <label class="storefront-field__label" for="profile-phone">{{ __('storefront::storefront.phone') }}</label>
                <input id="profile-phone" name="phone" value="{{ old('phone', $customer->phone) }}" class="storefront-input">
            </div>
            <div class="storefront-field">
                <button type="submit" class="storefront-btn">{{ __('storefront::storefront.update_profile') }}</button>
            </div>
        </form>
    </x-storefront.account.shell>
@endsection
