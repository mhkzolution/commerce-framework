@props([])

<div {{ $attributes->merge(['class' => 'storefront-auth-card']) }}>
    <x-storefront.auth.logo class="storefront-auth-card__logo" />
    {{ $slot }}
</div>
