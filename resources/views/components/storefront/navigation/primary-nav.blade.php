@props([
    'items' => [],
])

@if (count($items) > 0)
    <nav class="storefront-primary-nav" aria-label="{{ __('storefront::storefront.nav_primary') }}" data-primary-nav>
        <ul class="storefront-primary-nav__list">
            @foreach ($items as $item)
                <li
                    class="storefront-primary-nav__item {{ $item['type'] === 'mega' ? 'storefront-primary-nav__item--mega' : '' }}"
                    @if ($item['type'] === 'mega') data-mega-menu-item="{{ $item['id'] }}" @endif
                >
                    @if ($item['type'] === 'mega')
                        <button
                            type="button"
                            class="storefront-primary-nav__trigger {{ ($item['active'] ?? false) ? 'storefront-primary-nav__trigger--active' : '' }}"
                            data-mega-menu-trigger="{{ $item['id'] }}"
                            aria-expanded="false"
                            aria-haspopup="true"
                        >
                            {{ $item['label'] }}
                            <svg class="storefront-primary-nav__chevron" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>
                    @else
                        <a
                            href="{{ $item['url'] }}"
                            class="storefront-primary-nav__link {{ ($item['active'] ?? false) ? 'storefront-primary-nav__link--active' : '' }}"
                        >
                            {{ $item['label'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
@endif
