@props([
    'href' => null,
    'variant' => 'header',
])

@php
    $site = app(\Commerce\Contracts\Settings\SiteIdentityServiceInterface::class);
    $href ??= Route::has('storefront.shop.index') ? route('storefront.shop.index') : url('/');
    $logoUrl = $site->logoUrl($variant === 'admin' ? 'thumbnail' : null);
    $name = $site->name();

    $wrapperClass = match ($variant) {
        'auth' => 'storefront-auth-logo',
        'admin' => 'admin-brand-link',
        default => 'storefront-brand',
    };

    $imageClass = match ($variant) {
        'auth' => 'storefront-auth-logo__img',
        'admin' => 'admin-brand-logo',
        default => 'storefront-brand__logo',
    };

    $nameClass = match ($variant) {
        'auth' => 'storefront-auth-logo__name',
        'admin' => 'truncate text-sm font-semibold text-text',
        default => 'storefront-brand__name',
    };
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $wrapperClass]) }}>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $name }}" class="{{ $imageClass }}" decoding="async">
    @elseif ($variant === 'auth' || $variant === 'admin')
        <span class="{{ $variant === 'admin' ? 'admin-brand-mark' : 'storefront-auth-logo__mark' }}" aria-hidden="true">
            @if ($variant === 'admin')
                {{ strtoupper(substr($name, 0, 1)) }}
            @endif
        </span>
    @endif

    @if (! $logoUrl || $variant === 'auth')
        <span class="{{ $nameClass }}">{{ $name }}</span>
    @endif
</a>
