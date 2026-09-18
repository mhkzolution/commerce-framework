@props([
    'redirect' => null,
])

@php
    $redirect = is_string($redirect) && $redirect !== '' ? $redirect : url()->current();
    $loginUrl = route('storefront.account.login', ['redirect' => $redirect]);
@endphp

<div {{ $attributes->merge(['class' => 'storefront-price-login']) }}>
    <p class="storefront-price-login__copy">{{ __('storefront::storefront.login_to_see_price') }}</p>
    <a class="storefront-price-login__cta" href="{{ $loginUrl }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
            <polyline points="10 17 15 12 10 7" />
            <line x1="15" x2="3" y1="12" y2="12" />
        </svg>
        {{ __('storefront::storefront.login_to_see_price_cta') }}
    </a>
</div>
