@props([
    'items' => [],
    'cartItemCount' => 0,
])

@php
  /** @var \Commerce\Customers\Models\Customer|null $customer */
  $customer = auth('customer')->user();
  $initials = collect(explode(' ', (string) $customer?->name))
      ->filter()
      ->take(2)
      ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
      ->implode('');
@endphp

<x-storefront.navigation.drawer
    id="mobile-nav"
    :label="__('storefront::storefront.nav_menu')"
    class="storefront-drawer--mobile-nav"
>
    <div class="storefront-mobile-nav" data-mobile-nav>
        <section class="storefront-mobile-nav__section storefront-mobile-nav__section--profile">
            @auth('customer')
                <a href="{{ route('storefront.account') }}" class="storefront-mobile-nav__profile">
                    <span class="storefront-mobile-nav__avatar">{{ $initials !== '' ? $initials : 'U' }}</span>
                    <span class="storefront-mobile-nav__greeting">
                        {{ __('storefront::storefront.mobile_nav_greeting', ['name' => $customer->name]) }}
                    </span>
                </a>
            @else
                <a href="{{ route('storefront.account.login') }}" class="storefront-mobile-nav__profile">
                    <span class="storefront-mobile-nav__avatar storefront-mobile-nav__avatar--guest" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20c1.5-4 6.5-4 8-4s6.5 0 8 4" />
                        </svg>
                    </span>
                    <span class="storefront-mobile-nav__greeting">{{ __('storefront::storefront.sign_in') }}</span>
                </a>
            @endauth

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
                    <button type="button" class="storefront-mobile-nav__row storefront-mobile-nav__row--action storefront-mobile-nav__row--with-icon" data-drawer-open="wishlist">
                        <span class="storefront-mobile-nav__row-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path d="M12 20.5 4.5 12.8C2.7 11 2.4 8.2 3.8 6s4.2-2.4 6.4-1.1L12 6.3l2-1.4c2.2-1.3 5-.7 6.4 1.1s1.1 5-1.1 6.8L12 20.5Z" />
                            </svg>
                        </span>
                        <span>{{ __('storefront::storefront.wishlist') }}</span>
                    </button>
                </li>
                <li>
                    <button type="button" class="storefront-mobile-nav__row storefront-mobile-nav__row--action storefront-mobile-nav__row--with-icon" data-drawer-open="cart">
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
                    </button>
                </li>
                <li>
                    <a href="{{ route('storefront.account.orders') }}" class="storefront-mobile-nav__row storefront-mobile-nav__row--link storefront-mobile-nav__row--with-icon">
                        <span class="storefront-mobile-nav__row-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path d="M16 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z" />
                                <path d="M15 3v5h5" />
                                <path d="M8 13h8" />
                                <path d="M8 17h5" />
                            </svg>
                        </span>
                        <span>{{ __('storefront::storefront.account_orders') }}</span>
                    </a>
                </li>
            </ul>
        </section>
    </div>
</x-storefront.navigation.drawer>
