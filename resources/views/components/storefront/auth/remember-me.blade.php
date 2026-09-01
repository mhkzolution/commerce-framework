<label {{ $attributes->merge(['class' => 'storefront-auth-remember']) }}>
    <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="storefront-auth-remember__input">
    <span class="storefront-auth-remember__box" aria-hidden="true">
        <svg class="storefront-auth-remember__check" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 6 9 17l-5-5" />
        </svg>
    </span>
    <span class="storefront-auth-remember__label">{{ __('customers::auth.remember_me') }}</span>
</label>
