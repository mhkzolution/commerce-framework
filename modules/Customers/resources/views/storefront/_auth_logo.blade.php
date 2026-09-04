@php
    $href = route('storefront.shop.index');
@endphp

<a href="{{ $href }}" class="storefront-auth-logo storefront-auth-card__logo">
    @if (! empty($logoUrl))
        <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="storefront-auth-logo__img">
    @else
        <span class="storefront-auth-logo__mark" aria-hidden="true"></span>
        <span class="storefront-auth-logo__name">{{ $storeName }}</span>
    @endif
</a>
