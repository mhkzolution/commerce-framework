@extends('cart::layouts.auth')

@section('title', __('customers::auth.login_title'))

@section('content')
    <div class="storefront-auth-page" data-auth>
        <div class="storefront-auth-card">
            @include('customers::storefront._auth_logo')

            <header class="storefront-auth-heading storefront-auth-card__heading">
                <h1 class="storefront-auth-heading__title">{{ __('customers::auth.welcome') }}</h1>
                <p class="storefront-auth-heading__description">{{ __('customers::auth.welcome_description') }}</p>
            </header>

            <form
                method="POST"
                action="{{ route('storefront.account.login.store') }}"
                class="storefront-auth-form"
                data-auth-form
            >
                @csrf

                <div class="storefront-auth-form__panels">
                    <div class="storefront-auth-form__panel storefront-auth-form__panel--active">
                        <div class="storefront-auth-field">
                            <label class="storefront-auth-field__label" for="login-email">{{ __('customers::auth.email') }}</label>
                            <input
                                id="login-email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                                class="storefront-auth-field__input"
                            >
                        </div>

                        @include('customers::storefront._auth_password_field', [
                            'id' => 'login-password-email',
                            'name' => 'password',
                            'label' => __('customers::auth.password'),
                            'autocomplete' => 'current-password',
                        ])
                    </div>
                </div>

                @if (session('status'))
                    <div class="storefront-auth-form__notice" role="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="storefront-auth-form__error" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="storefront-auth-form__actions">
                    <label class="storefront-auth-remember">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="storefront-auth-remember__input">
                        <span class="storefront-auth-remember__box" aria-hidden="true">
                            <svg class="storefront-auth-remember__check" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        </span>
                        <span class="storefront-auth-remember__label">{{ __('customers::auth.remember_me') }}</span>
                    </label>

                    <button type="submit" class="storefront-auth-form__submit">
                        <span class="storefront-auth-form__submit-inner">
                            <span>{{ __('customers::auth.sign_in') }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                                <polyline points="10 17 15 12 10 7" />
                                <line x1="15" x2="3" y1="12" y2="12" />
                            </svg>
                        </span>
                    </button>
                </div>
            </form>

            <footer class="storefront-auth-footer storefront-auth-card__footer">
                <p class="storefront-auth-footer__register">
                    {{ __('customers::auth.no_account') }}
                    <a href="{{ route('storefront.account.register') }}" class="storefront-auth-footer__link">
                        {{ __('customers::auth.create_account') }}
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
