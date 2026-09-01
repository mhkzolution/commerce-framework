@inject('customerExperience', 'Commerce\Settings\Services\CustomerExperienceConfig')

@php
    $cx = $customerExperience->resolve();
@endphp

<script type="application/json" data-customer-experience-config>{!! json_encode($cx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

@if ($cx['quickView']['enabled'])
    <x-storefront.customer-experience.product-quick-view :config="$cx['quickView']" />
@endif

@if ($cx['notifications']['enabled'])
    <x-storefront.customer-experience.notification-toast :config="$cx['notifications']" />
@endif

@if ($cx['navigation']['backToTop'])
    <x-storefront.customer-experience.back-to-top :config="$cx['navigation']" />
@endif
