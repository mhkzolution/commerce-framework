@extends('cart::layouts.storefront')

@section('title', $seo['title'] ?? $post->title)

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
            <x-storefront.breadcrumb :items="[
                ['label' => __('cms::blog.title'), 'url' => route('storefront.cms.posts.index')],
                ['label' => $post->title],
            ]" />

            <header class="storefront-article__header">
                <x-storefront.blog.article-meta
                    :category="$blogService->categoryLabel($post)"
                    :category-url="$blogService->categoryUrl($post)"
                    :author="$blogService->authorName($post)"
                    :author-url="$blogService->authorUrl($post)"
                    :published-at="$post->published_at"
                    :reading-time="$blogService->readingTime($post)"
                />

                <h1 class="storefront-article__title">{{ $post->title }}</h1>

                @if ($post->excerpt)
                    <p class="storefront-article__dek">{{ $post->excerpt }}</p>
                @endif

                <div class="storefront-article__actions">
                    <x-storefront.share-button :url="url()->current()" :title="$post->title" />
                </div>
            </header>

            @if ($image = $blogService->featuredImageUrl($post, 'large'))
                <figure class="storefront-article__hero">
                    <img src="{{ $image }}" alt="{{ $post->title }}" class="storefront-article__hero-image">
                </figure>
            @endif

            <div class="storefront-article__layout">
                @if (count($formatted['toc']) > 1)
                    <x-storefront.blog.table-of-contents :items="$formatted['toc']" class="storefront-article__toc" />
                @endif

                <div class="storefront-article__content storefront-prose" data-article-content>
                    {!! $formatted['html'] !!}
                </div>
            </div>

            <x-storefront.blog.related-articles :posts="$relatedPosts" :blog-service="$blogService" />
        </article>

        <x-storefront.blog.sidebar
            class="storefront-blog-sidebar--article"
            :recent-posts="$recentPosts"
            :popular-tags="$popularTags"
            :blog-service="$blogService"
        />
    </div>
@endsection

@push('scripts')
    @vite('resources/js/storefront/blog.js')
@endpush
