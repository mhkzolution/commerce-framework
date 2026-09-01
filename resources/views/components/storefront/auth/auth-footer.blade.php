@props([
    'authConfig',
])

@php
    $support = $authConfig->support();
@endphp

<footer {{ $attributes->merge(['class' => 'storefront-auth-footer']) }}>
  @if ($authConfig->registrationEnabled())
    <p class="storefront-auth-footer__register">
      {{ __('customers::auth.no_account') }}
      <a href="{{ route('storefront.account.register') }}" class="storefront-auth-footer__link">
        {{ __('customers::auth.create_account') }}
      </a>
    </p>
  @endif

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
