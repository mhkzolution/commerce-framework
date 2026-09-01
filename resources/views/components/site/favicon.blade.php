@php
    $faviconUrl = app(\Commerce\Contracts\Settings\SiteIdentityServiceInterface::class)->faviconUrl();
@endphp

@if ($faviconUrl)
    <link rel="icon" href="{{ $faviconUrl }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
@endif
