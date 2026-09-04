@props([
    'header' => null,
])

@php
    use Commerce\Contracts\Storefront\HeaderViewData;

    $header = $header instanceof HeaderViewData ? $header : null;
    $authenticated = $header?->actions->authenticated ?? false;
@endphp

<div {{ $attributes->merge(['class' => 'storefront-header-account']) }}>
    @if ($authenticated)
        <x-storefront.navigation.user-menu :header="$header" />
    @else
        <x-storefront.navigation.guest-menu :header="$header" />
    @endif
</div>
