@props([
    'authConfig',
    'loginMode' => 'email',
])

@php
    $modes = $authConfig->loginModes();
    $oauthProviders = $authConfig->oauthProviders();
@endphp

<x-storefront.auth.recaptcha-script :auth-config="$authConfig" />

<form
    method="POST"
    action="{{ route('storefront.account.login.store') }}"
    class="storefront-auth-form"
    data-auth-form
    data-otp-send-url="{{ route('storefront.account.otp.send') }}"
>
    @csrf
    <input type="hidden" name="login_mode" value="{{ $loginMode }}" data-auth-mode-input>

    @if (count($modes) > 1)
        <x-storefront.auth.mode-tabs :modes="$modes" :active="$loginMode" />
    @endif

    <div class="storefront-auth-form__panels">
        <div class="storefront-auth-form__panel @if ($loginMode === 'email') storefront-auth-form__panel--active @endif" data-auth-panel="email">
            <x-storefront.auth.text-field
                id="login-email"
                name="email"
                type="email"
                :label="__('customers::auth.email')"
                :value="old('email')"
                autocomplete="email"
            />
            <x-storefront.auth.password-field
                id="login-password-email"
                name="password"
                :label="__('customers::auth.password')"
                autocomplete="current-password"
            />
            @if ($authConfig->forgotPasswordEnabled())
                <p class="storefront-auth-form__forgot">
                    <a href="{{ route('storefront.account.password.request') }}" class="storefront-auth-footer__link">
                        {{ __('customers::auth.forgot_password') }}
                    </a>
                </p>
            @endif
        </div>

        <div class="storefront-auth-form__panel @if ($loginMode === 'phone') storefront-auth-form__panel--active @endif" data-auth-panel="phone">
            <x-storefront.auth.text-field
                id="login-phone"
                name="phone"
                type="tel"
                :label="__('customers::auth.phone')"
                :value="old('phone')"
                autocomplete="tel"
                inputmode="tel"
            />
            <x-storefront.auth.password-field
                id="login-password-phone"
                name="password"
                :label="__('customers::auth.password')"
                autocomplete="current-password"
            />
            @if ($authConfig->forgotPasswordEnabled())
                <p class="storefront-auth-form__forgot">
                    <a href="{{ route('storefront.account.password.request') }}" class="storefront-auth-footer__link">
                        {{ __('customers::auth.forgot_password') }}
                    </a>
                </p>
            @endif
        </div>

        <div class="storefront-auth-form__panel @if ($loginMode === 'otp') storefront-auth-form__panel--active @endif" data-auth-panel="otp">
            <p class="storefront-auth-form__hint">{{ __('customers::auth.otp_hint') }}</p>
            <x-storefront.auth.text-field
                id="login-otp-identifier"
                name="identifier"
                :label="__('customers::auth.otp_identifier')"
                :value="old('identifier')"
                autocomplete="username"
            />
            <x-storefront.auth.text-field
                id="login-otp-code"
                name="otp"
                :label="__('customers::auth.otp_code')"
                :value="old('otp')"
                inputmode="numeric"
                autocomplete="one-time-code"
                :required="false"
            />
            <button
                type="button"
                class="storefront-auth-form__otp-send"
                data-otp-send
                data-otp-send-label="{{ __('customers::auth.otp_send') }}"
                data-otp-sending-label="{{ __('customers::auth.otp_sending') }}"
                @disabled(! $authConfig->otpEnabled())
            >
                {{ __('customers::auth.otp_send') }}
            </button>
            <p class="storefront-auth-form__otp-status" data-otp-status hidden role="status"></p>
        </div>
    </div>

    @session('status')
        <div class="cf-flash cf-flash--success storefront-auth-form__notice" role="status">{{ $value }}</div>
    @endsession

    @if ($errors->any())
        <div class="cf-flash cf-flash--danger storefront-auth-form__error" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="storefront-auth-form__actions">
        <x-storefront.auth.remember-me />
        <x-storefront.auth.recaptcha :enabled="$authConfig->recaptchaEnabled()" />
        <x-admin.button type="submit" variant="primary" class="storefront-auth-form__submit">
            <span class="storefront-auth-form__submit-inner">
                <span>{{ __('customers::auth.sign_in') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                    <polyline points="10 17 15 12 10 7" />
                    <line x1="15" x2="3" y1="12" y2="12" />
                </svg>
            </span>
        </x-admin.button>
    </div>
</form>

@if ($oauthProviders !== [])
    <x-storefront.auth.divider />
    <x-storefront.auth.social-buttons :providers="$oauthProviders" />
@endif
