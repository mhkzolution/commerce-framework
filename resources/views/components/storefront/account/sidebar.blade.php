@props([
    'customer',
    'active' => 'dashboard',
])

@php
    $initials = collect(explode(' ', (string) $customer->name))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<aside {{ $attributes->merge(['class' => 'storefront-account-sidebar']) }}>
    <div class="storefront-account-sidebar__profile">
        <div class="storefront-account-sidebar__avatar">{{ $initials !== '' ? $initials : 'U' }}</div>
        <p class="storefront-account-sidebar__name">{{ $customer->name }}</p>
        <p class="storefront-account-sidebar__email">{{ $customer->email }}</p>
    </div>

    <nav class="storefront-account-sidebar__nav" aria-label="{{ __('storefront::storefront.account') }}">
        <a
            href="{{ route('storefront.account') }}"
            class="storefront-account-sidebar__link {{ $active === 'dashboard' ? 'storefront-account-sidebar__link--active' : '' }}"
        >
            {{ __('storefront::storefront.account_dashboard') }}
        </a>
        <a
            href="{{ route('storefront.account.orders') }}"
            class="storefront-account-sidebar__link {{ $active === 'orders' ? 'storefront-account-sidebar__link--active' : '' }}"
        >
            {{ __('storefront::storefront.account_orders') }}
        </a>
        <a
            href="{{ route('storefront.account.wishlist') }}"
            class="storefront-account-sidebar__link {{ $active === 'wishlist' ? 'storefront-account-sidebar__link--active' : '' }}"
        >
            {{ __('storefront::storefront.wishlist') }}
        </a>
        <a
            href="{{ route('storefront.account.shipping') }}"
            class="storefront-account-sidebar__link {{ $active === 'shipping' ? 'storefront-account-sidebar__link--active' : '' }}"
        >
            {{ __('storefront::storefront.account_shipping') }}
        </a>
        <a
            href="{{ route('storefront.account.profile') }}"
            class="storefront-account-sidebar__link {{ $active === 'profile' ? 'storefront-account-sidebar__link--active' : '' }}"
        >
            {{ __('storefront::storefront.account_profile') }}
        </a>

        <form method="POST" action="{{ route('storefront.account.logout') }}">
            @csrf
            <button type="submit" class="storefront-account-sidebar__logout">
                {{ __('storefront::storefront.sign_out') }}
            </button>
        </form>
    </nav>
</aside>
