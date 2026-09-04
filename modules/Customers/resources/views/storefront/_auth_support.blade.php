@if (! empty($supportEmail) || ! empty($supportPhone))
    <div class="storefront-auth-footer__support">
        <p class="storefront-auth-footer__support-label">{{ __('customers::auth.support') }}</p>
        <div class="storefront-auth-footer__support-links">
            @if (! empty($supportEmail))
                <a href="mailto:{{ $supportEmail }}" class="storefront-auth-footer__link">{{ __('customers::auth.support_email') }}</a>
            @endif
            @if (! empty($supportPhone))
                <a href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}" class="storefront-auth-footer__link">{{ __('customers::auth.support_phone') }}</a>
            @endif
        </div>
    </div>
@endif
