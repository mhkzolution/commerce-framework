@props([
    'recaptchaEnabled' => false,
])

<form
    method="POST"
    action="{{ route('storefront.account.register.store') }}"
    class="storefront-auth-form"
    data-auth-form
>
    @csrf

    {{-- Honeypot: hidden from users, bots often fill it --}}
    <div class="sr-only" aria-hidden="true">
        <label for="register-website">Website</label>
        <input id="register-website" type="text" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="storefront-auth-form__panels">
        <div class="storefront-auth-form__panel storefront-auth-form__panel--active">
            <x-storefront.auth.text-field
                id="register-name"
                name="name"
                :label="__('customers::auth.name')"
                :value="old('name')"
                autocomplete="name"
            />
            <x-storefront.auth.text-field
                id="register-email"
                name="email"
                type="email"
                :label="__('customers::auth.email')"
                :value="old('email')"
                autocomplete="email"
            />
            <x-storefront.auth.text-field
                id="register-phone"
                name="phone"
                type="tel"
                :label="__('customers::auth.phone')"
                :value="old('phone')"
                autocomplete="tel"
                inputmode="tel"
                :required="false"
            />
            <x-storefront.auth.password-field
                id="register-password"
                name="password"
                :label="__('customers::auth.password')"
                autocomplete="new-password"
            />
            <x-storefront.auth.password-field
                id="register-password-confirmation"
                name="password_confirmation"
                :label="__('customers::auth.confirm_password')"
                autocomplete="new-password"
            />
        </div>
    </div>

    @if ($errors->any())
        <div class="cf-flash cf-flash--danger storefront-auth-form__error" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="storefront-auth-form__actions">
        <x-storefront.auth.recaptcha :enabled="$recaptchaEnabled" />
        <x-admin.button type="submit" variant="primary" class="storefront-auth-form__submit">
            {{ __('customers::auth.register_submit') }}
        </x-admin.button>
    </div>
</form>
