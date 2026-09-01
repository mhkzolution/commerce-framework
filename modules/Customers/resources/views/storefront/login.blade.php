@extends('cart::layouts.auth')

@section('title', __('customers::auth.login_title'))

@section('content')
    <div class="storefront-auth-page" data-auth
        @if ($authConfig->recaptchaEnabled())
            data-recaptcha-enabled="1"
            data-recaptcha-site-key="{{ config('customers.storefront.recaptcha.site_key') }}"
        @endif
    >
        <x-storefront.auth.auth-card>
            <x-storefront.auth.heading
                :title="__('customers::auth.welcome')"
                :description="__('customers::auth.welcome_description')"
                class="storefront-auth-card__heading"
            />

            <x-storefront.auth.login-form :auth-config="$authConfig" :login-mode="$loginMode" />

            <x-storefront.auth.auth-footer :auth-config="$authConfig" class="storefront-auth-card__footer" />
        </x-storefront.auth.auth-card>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/storefront/auth.js')
@endpush
