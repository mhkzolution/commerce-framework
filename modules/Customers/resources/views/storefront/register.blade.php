@extends('cart::layouts.auth')

@section('title', __('customers::auth.register_title'))

@section('content')
    <div class="storefront-auth-page" data-auth>
        <div class="storefront-auth-card">
            @include('customers::storefront._auth_logo')

            <header class="storefront-auth-heading storefront-auth-card__heading">
                <h1 class="storefront-auth-heading__title">{{ __('customers::auth.register_title') }}</h1>
                <p class="storefront-auth-heading__description">{{ __('customers::auth.register_description') }}</p>
            </header>

            <form
                method="POST"
                action="{{ route('storefront.account.register.store') }}"
                class="storefront-auth-form"
                data-auth-form
            >
                @csrf

                <div class="sr-only" aria-hidden="true">
                    <label for="register-website">Website</label>
                    <input id="register-website" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="storefront-auth-form__panels">
                    <div class="storefront-auth-form__panel storefront-auth-form__panel--active">
                        <div class="storefront-auth-field">
                            <label class="storefront-auth-field__label" for="register-name">{{ __('customers::auth.name') }}</label>
                            <input id="register-name" name="name" value="{{ old('name') }}" required autocomplete="name" class="storefront-auth-field__input">
                        </div>
                        <div class="storefront-auth-field">
                            <label class="storefront-auth-field__label" for="register-email">{{ __('customers::auth.email') }}</label>
                            <input id="register-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="storefront-auth-field__input">
                        </div>
                        <div class="storefront-auth-field">
                            <label class="storefront-auth-field__label" for="register-phone">{{ __('customers::auth.phone') }}</label>
                            <input id="register-phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" class="storefront-auth-field__input">
                        </div>
                        @include('customers::storefront._auth_password_field', [
                            'id' => 'register-password',
                            'name' => 'password',
                            'label' => __('customers::auth.password'),
                            'autocomplete' => 'new-password',
                        ])
                        @include('customers::storefront._auth_password_field', [
                            'id' => 'register-password-confirmation',
                            'name' => 'password_confirmation',
                            'label' => __('customers::auth.confirm_password'),
                            'autocomplete' => 'new-password',
                        ])
                    </div>
                </div>

                @if ($errors->any())
                    <div class="storefront-auth-form__error" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="storefront-auth-form__actions">
                    <button type="submit" class="storefront-auth-form__submit">
                        {{ __('customers::auth.register_submit') }}
                    </button>
                </div>
            </form>

            <footer class="storefront-auth-footer storefront-auth-card__footer">
                <p class="storefront-auth-footer__register">
                    {{ __('customers::auth.already_have_account') }}
                    <a href="{{ route('storefront.account.login') }}" class="storefront-auth-footer__link">
                        {{ __('customers::auth.sign_in') }}
                    </a>
                </p>
                @include('customers::storefront._auth_support')
            </footer>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/storefront/auth.js')
@endpush
