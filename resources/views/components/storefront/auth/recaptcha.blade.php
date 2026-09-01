@props([
    'enabled' => false,
])

@if ($enabled)
    <input type="hidden" name="g-recaptcha-response" value="" data-recaptcha-token>
@endif
