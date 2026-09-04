@props([
    'message' => '',
    'dismissible' => true,
])

@if ($message !== '')
    <div
        class="storefront-promo-bar"
        data-promo-bar
        @if ($dismissible) data-promo-dismissible @endif
    >
        <p class="storefront-promo-bar__message">{{ $message }}</p>
        @if ($dismissible)
            <button
                type="button"
                class="storefront-promo-bar__close"
                data-promo-dismiss
                aria-label="{{ __('storefront::storefront.close') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        @endif
    </div>
@endif
