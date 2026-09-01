@extends('cart::layouts.auth')

@section('title', __('customers::auth.register_title'))

@section('content')
    <div class="storefront-auth-page" data-auth
        @if ($authConfig->recaptchaEnabled())
            data-recaptcha-enabled="1"
            data-recaptcha-site-key="{{ config('customers.storefront.recaptcha.site_key') }}"
        @endif
    >
        <x-storefront.auth.auth-card>
            <x-storefront.auth.heading
                :title="__('customers::auth.register_title')"
                :description="__('customers::auth.register_description')"
                class="storefront-auth-card__heading"
            />

            <x-storefront.auth.recaptcha-script :auth-config="$authConfig" />
            <x-storefront.auth.register-form :recaptcha-enabled="$authConfig->recaptchaEnabled()" />

            @php($oauthProviders = $authConfig->oauthProviders())
            @if (count($oauthProviders) > 0)
                <x-storefront.auth.divider />
                <x-storefront.auth.social-buttons :providers="$oauthProviders" />
            @endif

            <footer class="storefront-auth-footer storefront-auth-card__footer">
                <p class="storefront-auth-footer__register">
                    {{ __('customers::auth.already_have_account') }}
                    <a href="{{ route('storefront.account.login') }}" class="storefront-auth-footer__link">
                        {{ __('customers::auth.sign_in') }}
                    </a>
                </p>

                @php($support = $authConfig->support())
                @if ($support['email'] || $support['phone'])
                    <div class="storefront-auth-footer__support">
                        <p class="storefront-auth-footer__support-label">{{ __('customers::auth.support') }}</p>
                        <div class="storefront-auth-footer__support-links">
                            @if ($support['email'])
                                <a href="mailto:{{ $support['email'] }}" class="storefront-auth-footer__link">{{ __('customers::auth.support_email') }}</a>
                            @endif
                            @if ($support['phone'])
                                <a href="tel:{{ preg_replace('/\s+/', '', $support['phone']) }}" class="storefront-auth-footer__link">{{ __('customers::auth.support_phone') }}</a>
                            @endif
                        </div>
                    </div>
                @endif
            </footer>
        </x-storefront.auth.auth-card>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/storefront/auth.js')
@endpush
