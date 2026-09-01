@extends('cart::layouts.auth')

@section('title', __('customers::auth.reset_password_title'))

@section('content')
    <div class="storefront-auth-page" data-auth>
        <x-storefront.auth.auth-card>
            <x-storefront.auth.heading
                :title="__('customers::auth.reset_password_title')"
                :description="__('customers::auth.reset_password_description')"
                class="storefront-auth-card__heading"
            />

            @if ($errors->any())
                <div class="cf-flash cf-flash--danger storefront-auth-form__error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('storefront.account.password.update') }}" class="storefront-auth-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="storefront-auth-form__panels">
                    <div class="storefront-auth-form__panel storefront-auth-form__panel--active">
                        <x-storefront.auth.text-field
                            id="reset-email"
                            name="email"
                            type="email"
                            :label="__('customers::auth.email')"
                            :value="old('email', $email)"
                            autocomplete="email"
                        />
                        <x-storefront.auth.password-field
                            id="reset-password"
                            name="password"
                            :label="__('customers::auth.password')"
                            autocomplete="new-password"
                        />
                        <x-storefront.auth.password-field
                            id="reset-password-confirmation"
                            name="password_confirmation"
                            :label="__('customers::auth.confirm_password')"
                            autocomplete="new-password"
                        />
                    </div>
                </div>

                <div class="storefront-auth-form__actions">
                    <x-admin.button type="submit" variant="primary" class="storefront-auth-form__submit">
                        {{ __('customers::auth.reset_password_submit') }}
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

@push('scripts')
    @vite('resources/js/storefront/auth.js')
@endpush
