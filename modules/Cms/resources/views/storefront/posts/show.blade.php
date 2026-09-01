@extends('cart::layouts.storefront')

@section('title', $seo['title'] ?? $post->title)
@section('main_class', 'storefront-blog-shell')

@push('head')
    <x-storefront.seo-meta :meta="$seo" />
    <x-storefront.json-ld :data="$structuredData ?? null" />
    <meta property="og:type" content="article">
    @if ($post->published_at)
        <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
    @endif
@endpush

@section('content')
    <div class="storefront-article-page">
        @if (! empty($isPreview))
            <p class="storefront-article__preview-banner">{{ __('cms::blog.preview_notice') }}</p>
        @endif

        <article class="storefront-article" data-article>
            <div class="storefront-article__header-band">
                <div class="storefront-article__breadcrumb">
                    <x-storefront.breadcrumb :items="array_values(array_filter([
                        ['label' => __('cms::blog.home'), 'url' => route('storefront.shop.index')],
                        ['label' => __('cms::blog.title'), 'url' => route('storefront.cms.posts.index')],
                        $blogService->categoryLabel($post) ? [
                            'label' => $blogService->categoryLabel($post),
                            'url' => $blogService->categoryUrl($post),
                        ] : null,
                    ]))" />
                </div>

                <header class="storefront-article__header">
                    @if ($blogService->categoryLabel($post))
                        <a href="{{ $blogService->categoryUrl($post) }}" class="storefront-article__category">
                            {{ $blogService->categoryLabel($post) }}
                        </a>
                    @endif

                    <h1 class="storefront-article__title">{{ $post->title }}</h1>

                    @if ($post->excerpt)
                        <p class="storefront-article__dek">{{ $post->excerpt }}</p>
                    @endif

                    <x-storefront.blog.article-meta
                        :author="$blogService->authorName($post)"
                        :author-url="$blogService->authorUrl($post)"
                        :published-at="$post->published_at"
                        :reading-time="$blogService->readingTime($post)"
                    />
                </header>
            </div>

            @if ($image = $blogService->featuredImageUrl($post, 'large'))
                <figure class="storefront-article__hero">
                    <img src="{{ $image }}" alt="{{ $post->title }}" class="storefront-article__hero-image">
                </figure>
            @endif

            <div class="storefront-article__body">
                @if (count($formatted['toc']) > 1)
                    <x-storefront.blog.table-of-contents :items="$formatted['toc']" class="storefront-article__toc" />
                @endif

                <div class="storefront-article__content storefront-prose" data-article-content>
                    {!! $formatted['html'] !!}
                </div>

                <x-storefront.blog.share :url="url()->current()" :title="$post->title" />
            </div>
        </article>

        <x-storefront.blog.related-articles :posts="$relatedPosts" :blog-service="$blogService" />

        <div class="storefront-article-end">
            <a href="{{ route('storefront.cms.posts.index') }}" class="storefront-article-end__cta">
                {{ __('cms::blog.browse_more') }}
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/storefront/blog.js')
@endpush
