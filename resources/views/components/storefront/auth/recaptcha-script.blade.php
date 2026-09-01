@props([
    'authConfig',
])

@if ($authConfig->recaptchaEnabled())
    @push('head')
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('customers.storefront.recaptcha.site_key') }}"></script>
    @endpush
@endif
