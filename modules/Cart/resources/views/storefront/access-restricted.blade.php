@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.access_restricted_title'))
@section('main_class', 'storefront-access-main')

@push('head')
    <x-storefront.seo-meta :meta="$pageSeo ?? ['robots' => 'noindex,nofollow']" />
@endpush

@section('content')
    <x-storefront.layout.page-container variant="narrow">
        <section class="storefront-access-gate" data-store-access-restricted>
            <div class="storefront-access-gate__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
            </div>
            <h1 class="storefront-access-gate__title">{{ __('storefront::storefront.access_restricted_title') }}</h1>
            <p class="storefront-access-gate__body">{{ __('storefront::storefront.access_restricted_body') }}</p>
            <p class="storefront-access-gate__hint">{{ __('storefront::storefront.access_restricted_hint') }}</p>
            <div class="storefront-access-gate__actions">
                <a class="storefront-access-gate__cta" href="{{ $loginUrl }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                        <polyline points="10 17 15 12 10 7" />
                        <line x1="15" x2="3" y1="12" y2="12" />
                    </svg>
                    {{ __('storefront::storefront.login_to_see_price_cta') }}
                </a>
                <a class="storefront-access-gate__cta storefront-access-gate__cta--secondary" href="{{ $registerUrl }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <line x1="19" x2="19" y1="8" y2="14" />
                        <line x1="22" x2="16" y1="11" y2="11" />
                    </svg>
                    {{ __('customers::auth.create_account') }}
                </a>
            </div>
        </section>
    </x-storefront.layout.page-container>
@endsection
