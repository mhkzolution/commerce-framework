@props([
    'items' => [],
    'cartItemCount' => 0,
    'header' => null,
])

@php
    use Commerce\Contracts\Storefront\HeaderViewData;

    $header = $header instanceof HeaderViewData ? $header : null;
    $authenticated = $header?->actions->authenticated ?? false;
    $initials = $header?->actions->customerInitials ?: 'U';
    $name = $header?->actions->customerName ?: __('storefront::storefront.account');
@endphp

<x-storefront.navigation.drawer
    id="mobile-nav"
    :label="__('storefront::storefront.nav_menu')"
    class="storefront-drawer--mobile-nav"
>
    <div class="storefront-mobile-nav" data-mobile-nav>
        <section class="storefront-mobile-nav__section storefront-mobile-nav__section--profile">
            @if ($authenticated)
                <a href="{{ $header->actions->accountUrl }}" class="storefront-mobile-nav__profile">
                    <span class="storefront-mobile-nav__avatar">{{ $initials }}</span>
                    <span class="storefront-mobile-nav__greeting">
                        {{ __('storefront::storefront.mobile_nav_greeting', ['name' => $name]) }}
                    </span>
                </a>
            @else
                <a href="{{ $header?->actions->loginUrl ?? route('storefront.account.login') }}" class="storefront-mobile-nav__profile">
                    <span class="storefront-mobile-nav__avatar storefront-mobile-nav__avatar--guest" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20c1.5-4 6.5-4 8-4s6.5 0 8 4" />
                        </svg>
                    </span>
                    <span class="storefront-mobile-nav__greeting">{{ __('storefront::storefront.sign_in') }}</span>
                </a>
            @endif

            <button
                type="button"
                class="storefront-mobile-nav__close"
                data-drawer-close="mobile-nav"
                aria-label="{{ __('storefront::storefront.close') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </section>

        <section class="storefront-mobile-nav__section storefront-mobile-nav__section--menu">
            <div class="storefront-mobile-nav__panels">
                <div class="storefront-mobile-nav__panel storefront-mobile-nav__panel--root" data-mobile-nav-panel="root">
                    <ul class="storefront-mobile-nav__list">
                        @foreach ($items as $item)
                            <li>
                                @if ($item['type'] === 'mega' && count($item['columns'] ?? []) > 0)
                                    <button
                                        type="button"
                                        class="storefront-mobile-nav__row"
                                        data-mobile-nav-open="{{ $item['id'] }}"
                                    >
                                        <span>{{ $item['label'] }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                            <path d="m9 18 6-6-6-6" />
                                        </svg>
                                    </button>
                                @else
                                    <a href="{{ $item['url'] }}" class="storefront-mobile-nav__row storefront-mobile-nav__row--link">
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                @foreach ($items as $item)
                    @if ($item['type'] !== 'mega' || count($item['columns'] ?? []) === 0)
                        @continue
                    @endif

                    <div class="storefront-mobile-nav__panel" data-mobile-nav-panel="{{ $item['id'] }}" hidden>
                        <button type="button" class="storefront-mobile-nav__back" data-mobile-nav-back>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                <path d="m15 18-6-6 6-6" />
                            </svg>
                            <span>{{ $item['label'] }}</span>
                        </button>

                        @foreach ($item['columns'] as $column)
                            @if (! empty($column['title']))
                                <p class="storefront-mobile-nav__section-title">{{ $column['title'] }}</p>
                            @endif

                            <ul class="storefront-mobile-nav__list">
                                @foreach ($column['links'] ?? [] as $link)
                                    <li>
                                        <a href="{{ $link['url'] }}" class="storefront-mobile-nav__row storefront-mobile-nav__row--link">
                                            <span>{{ $link['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            @if (! empty($column['view_all']))
                                <a href="{{ $column['view_all']['url'] }}" class="storefront-mobile-nav__view-all">
                                    {{ $column['view_all']['label'] }} →
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>

        <section class="storefront-mobile-nav__section storefront-mobile-nav__section--actions">
            <ul class="storefront-mobile-nav__list">
                <li>
                    <a href="{{ $header?->actions->cartUrl ?? route('storefront.cart.index') }}" class="storefront-mobile-nav__row storefront-mobile-nav__row--link storefront-mobile-nav__row--with-icon">
                        <span class="storefront-mobile-nav__row-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path d="M6 6h15l-1.5 9h-12z" />
                                <circle cx="9" cy="20" r="1" />
                                <circle cx="18" cy="20" r="1" />
                                <path d="M6 6 5 3H2" />
                            </svg>
                        </span>
                        <span>{{ __('storefront::storefront.cart') }}</span>
                        @if ($cartItemCount > 0)
                            <span class="storefront-mobile-nav__badge">{{ $cartItemCount }}</span>
                        @endif
                    </a>
                </li>
            </ul>
        </section>
    </div>
</x-storefront.navigation.drawer>
