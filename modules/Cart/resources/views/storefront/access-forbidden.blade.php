@extends('cart::layouts.storefront')

@section('title', __('storefront::storefront.access_forbidden_title'))
@section('main_class', 'storefront-access-main')

@push('head')
    <x-storefront.seo-meta :meta="$pageSeo ?? ['robots' => 'noindex,nofollow']" />
@endpush

@section('content')
    <x-storefront.layout.page-container variant="narrow">
        <section class="storefront-access-gate" data-store-access-forbidden>
            <div class="storefront-access-gate__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="4.93" x2="19.07" y1="4.93" y2="19.07" />
                </svg>
            </div>
            <h1 class="storefront-access-gate__title">{{ __('storefront::storefront.access_forbidden_title') }}</h1>
            <p class="storefront-access-gate__body">{{ __('storefront::storefront.access_forbidden_body') }}</p>
            <div class="storefront-access-gate__actions">
                <a class="storefront-access-gate__cta storefront-access-gate__cta--secondary" href="{{ route('storefront.account') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M4 20c0-4 4-6 8-6s8 2 8 6" />
                    </svg>
                    {{ __('storefront::storefront.account') }}
                </a>
            </div>
        </section>
    </x-storefront.layout.page-container>
@endsection
