@props([
    'cx' => null,
])

@php
    $config = is_array($cx) ? $cx : null;
@endphp

@if (is_array($config))
    <script type="application/json" data-customer-experience-config>{!! json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

    @if ($config['quickView']['enabled'] ?? false)
        <x-storefront.customer-experience.product-quick-view :config="$config['quickView']" />
    @endif

    @if ($config['notifications']['enabled'] ?? false)
        <x-storefront.customer-experience.notification-toast :config="$config['notifications']" />
    @endif

    @if ($config['navigation']['backToTop'] ?? false)
        <x-storefront.customer-experience.back-to-top :config="$config['navigation']" />
    @endif
@endif
