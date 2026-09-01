@extends('cart::layouts.storefront')

@section('title', $seo['title'] ?? $page->title)

@push('head')
    <x-storefront.seo-meta :meta="$seo ?? null" />
    <x-storefront.json-ld :data="$structuredData ?? null" />
@endpush

@section('content')
    <article class="prose max-w-none">
        <h1 class="text-3xl font-semibold text-text">{{ $page->title }}</h1>
        <div class="mt-6 text-text-secondary whitespace-pre-wrap">{{ $page->content }}</div>
    </article>
@endsection
