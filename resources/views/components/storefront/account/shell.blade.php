@props([
    'customer',
    'title',
    'description' => null,
    'section' => 'dashboard',
])

@php
    /** @var \Commerce\Customers\Models\Customer $customer */
    $parts = preg_split('/\s+/', trim((string) $customer->name)) ?: [];
    $initials = collect($parts)
        ->filter()
        ->map(static fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');
    $initials = $initials !== '' ? $initials : 'U';

    $links = [
        ['key' => 'dashboard', 'route' => 'storefront.account', 'label' => __('storefront::storefront.account_dashboard')],
        ['key' => 'orders', 'route' => 'storefront.account.orders', 'label' => __('storefront::storefront.orders')],
        ['key' => 'addresses', 'route' => 'storefront.account.addresses', 'label' => __('storefront::storefront.addresses')],
        ['key' => 'wishlist', 'route' => 'storefront.account.wishlist', 'label' => __('storefront::storefront.wishlist')],
        ['key' => 'profile', 'route' => 'storefront.account.profile', 'label' => __('storefront::storefront.profile')],
        ['key' => 'security', 'route' => 'storefront.account.security', 'label' => __('storefront::storefront.security')],
    ];
@endphp

<x-storefront.layout.page-container class="storefront-shopper storefront-account">
    <div class="storefront-account__layout">
        <details class="storefront-account-menu" open>
            <summary class="storefront-account-menu__summary">{{ __('storefront::storefront.account_menu') }}</summary>

            <aside class="storefront-account-sidebar">
                <div class="storefront-account-sidebar__profile">
                    <span class="storefront-account-sidebar__avatar" aria-hidden="true">{{ $initials }}</span>
                    <p class="storefront-account-sidebar__name">{{ $customer->name }}</p>
                    <p class="storefront-account-sidebar__email">{{ $customer->email }}</p>
                </div>

                <nav class="storefront-account-sidebar__nav" aria-label="{{ __('storefront::storefront.account_menu') }}">
                    @foreach ($links as $link)
                        @php
                            $active = $section === $link['key'];
                        @endphp
                        <a
                            href="{{ route($link['route']) }}"
                            class="storefront-account-sidebar__link{{ $active ? ' storefront-account-sidebar__link--active' : '' }}"
                            @if ($active) aria-current="page" @endif
                        >
                            {{ $link['label'] }}
                        </a>
                    @endforeach

                    <form method="POST" action="{{ route('storefront.account.logout') }}">
                        @csrf
                        <button type="submit" class="storefront-account-sidebar__logout">
                            {{ __('storefront::storefront.sign_out') }}
                        </button>
                    </form>
                </nav>
            </aside>
        </details>

        <div class="storefront-account-content">
            <h1 class="storefront-account-content__title">{{ $title }}</h1>
            @if ($description)
                <p class="storefront-account-content__description">{{ $description }}</p>
            @endif

            @session('status')
                <div class="storefront-flash storefront-flash--success">{{ $value }}</div>
            @endsession

            @if ($errors->any())
                <div class="storefront-flash storefront-flash--danger">{{ $errors->first() }}</div>
            @endif

            {{ $slot }}
        </div>
    </div>
</x-storefront.layout.page-container>
