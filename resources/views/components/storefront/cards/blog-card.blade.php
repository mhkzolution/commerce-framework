@props([
    'post',
    'blogService',
    'featured' => false,
])

@php
    $url = route('storefront.cms.posts.show', $post->slug);
    $imageUrl = $blogService->featuredImageUrl($post, 'medium');
    $category = $blogService->categoryLabel($post);
    $author = $blogService->authorName($post);
    $readingTime = $blogService->readingTime($post);
@endphp

<article {{ $attributes->merge(['class' => 'storefront-article-card' . ($featured ? ' storefront-article-card--featured' : '')]) }}>
    <a href="{{ $url }}" class="storefront-article-card__media" aria-hidden="true" tabindex="-1">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="" class="storefront-article-card__image" loading="lazy" decoding="async">
        @else
            <div class="storefront-article-card__placeholder"></div>
        @endif
    </a>

    <div class="storefront-article-card__body">
        <x-storefront.blog.article-meta
            :category="$category"
            :category-url="$blogService->categoryUrl($post)"
            :author="$author"
            :author-url="$blogService->authorUrl($post)"
            :published-at="$post->published_at"
            :reading-time="$readingTime"
        />

        <h2 class="storefront-article-card__title">
            <a href="{{ $url }}">{{ $post->title }}</a>
        </h2>

        @if ($post->excerpt)
            <p class="storefront-article-card__excerpt">{{ $post->excerpt }}</p>
        @endif
    </div>
</article>
