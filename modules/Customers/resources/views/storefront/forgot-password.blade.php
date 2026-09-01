@extends('cart::layouts.auth')

@section('title', __('customers::auth.forgot_password_title'))

@section('content')
    <div class="storefront-auth-page" data-auth
        @if ($authConfig->recaptchaEnabled())
            data-recaptcha-enabled="1"
            data-recaptcha-site-key="{{ config('customers.storefront.recaptcha.site_key') }}"
        @endif
    >
        <x-storefront.auth.auth-card>
            <x-storefront.auth.heading
                :title="__('customers::auth.forgot_password_title')"
                :description="__('customers::auth.forgot_password_description')"
                class="storefront-auth-card__heading"
            />

            <x-storefront.auth.recaptcha-script :auth-config="$authConfig" />

            @session('status')
                <div class="cf-flash cf-flash--success storefront-auth-form__notice" role="status">{{ $value }}</div>
            @endsession

            @if ($errors->any())
                <div class="cf-flash cf-flash--danger storefront-auth-form__error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('storefront.account.password.email') }}" class="storefront-auth-form">
                @csrf
                <div class="storefront-auth-form__panels">
                    <div class="storefront-auth-form__panel storefront-auth-form__panel--active">
                        <x-storefront.auth.text-field
                            id="forgot-email"
                            name="email"
                            type="email"
                            :label="__('customers::auth.email')"
                            :value="old('email')"
                            autocomplete="email"
                        />
                    </div>
                </div>

                <div class="storefront-auth-form__actions">
                    <x-storefront.auth.recaptcha :enabled="$authConfig->recaptchaEnabled()" />
                    <x-admin.button type="submit" variant="primary" class="storefront-auth-form__submit">
                        {{ __('customers::auth.forgot_password_submit') }}
                    </x-admin.button>
                </div>
            </form>

            <footer class="storefront-auth-footer storefront-auth-card__footer">
                <p class="storefront-auth-footer__register">
                    <a href="{{ route('storefront.account.login') }}" class="storefront-auth-footer__link">
                        {{ __('customers::auth.back_to_sign_in') }}
                    </a>
                </p>
            </footer>
        </x-storefront.auth.auth-card>
    </div>
@endsection
