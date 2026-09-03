@extends('cart::layouts.storefront')

@section('title', $pageSeo['title'] ?? __('storefront::storefront.home'))
@section('main_class', 'storefront-home-main')

@push('head')
    <x-storefront.seo-meta :meta="$pageSeo ?? null" />
    <x-storefront.json-ld :data="$structuredData ?? null" />
@endpush

@section('content')
    <div class="storefront-home" data-storefront-home data-arrivals-url="{{ route('storefront.home.arrivals') }}">
        <h1 class="sr-only">{{ __('storefront::storefront.home') }}</h1>
        @foreach ($homepageSections as $section)
            @continue(! ($section['is_active'] ?? false))
            @includeIf('cart::storefront.partials.home-section-'.$section['key'], ['section' => $section])
        @endforeach
    </div>
@endsection

@push('scripts')
    @vite(['resources/css/storefront/home.css', 'resources/js/storefront/home.js'])
@endpush
