@php
    $searchQuery = request()->query('search');
    $primaryNav = $headerPrimaryNavigation ?? ['promo' => ['enabled' => false], 'items' => []];
@endphp

@php
    $searchQuery = request()->query('search');
    $primaryNav = $headerPrimaryNavigation ?? ['promo' => ['enabled' => false], 'items' => []];
@endphp

<x-storefront.navigation.search-overlay
    :search="$searchQuery"
/>

<x-storefront.navigation.mobile-nav-drawer
    :items="$primaryNav['items'] ?? []"
    :cart-item-count="$headerCart->itemCount ?? 0"
/>

<x-storefront.navigation.cart-drawer :cart="$headerCart" :page="$headerCartPage" />

<x-storefront.navigation.wishlist-drawer />

<x-storefront.customer-experience.root />
