@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.security'))
@section('main_class', 'storefront-shopper-main')

@push('head')
    @vite('resources/css/storefront/shopper.css')
@endpush

@section('content')
    <x-storefront.account.shell
        :customer="$customer"
        :title="__('storefront::storefront.security')"
        :description="__('storefront::storefront.security_description')"
        section="security"
    >
        <form method="POST" action="{{ route('storefront.account.security.password') }}" class="storefront-stack">
            @csrf
            @method('PUT')
            <div class="storefront-field">
                <label class="storefront-field__label" for="current-password">{{ __('storefront::storefront.current_password') }}</label>
                <input id="current-password" name="current_password" type="password" autocomplete="current-password" class="storefront-input" required>
            </div>
            <div class="storefront-field">
                <label class="storefront-field__label" for="new-password">{{ __('storefront::storefront.new_password') }}</label>
                <input id="new-password" name="password" type="password" autocomplete="new-password" class="storefront-input" required>
                <p class="storefront-muted">{{ __('storefront::storefront.password_hint') }}</p>
            </div>
            <div class="storefront-field">
                <label class="storefront-field__label" for="new-password-confirmation">{{ __('storefront::storefront.confirm_password') }}</label>
                <input id="new-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="storefront-input" required>
            </div>
            <div>
                <button type="submit" class="storefront-btn">{{ __('storefront::storefront.change_password') }}</button>
            </div>
        </form>
    </x-storefront.account.shell>
@endsection
