@props(['config' => []])

<div
    class="cx-toast-host cx-toast-host--{{ $config['position'] ?? 'bottom-right' }}"
    data-notification-host
    data-notification-config='@json($config)'
    data-notification-url="{{ route('api.v1.storefront.customer-experience.notifications') }}"
    aria-live="polite"
></div>
