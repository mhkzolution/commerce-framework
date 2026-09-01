@props([
    'post',
    'blogService',
])

@php
    $url = route('storefront.cms.posts.show', $post->slug);
    $imageUrl = $blogService->featuredImageUrl($post, 'large')
        ?? $blogService->featuredImageUrl($post, 'medium');
    $category = $blogService->categoryLabel($post);
    $author = $blogService->authorName($post);
    $readingTime = $blogService->readingTime($post);
@endphp

<article {{ $attributes->merge(['class' => 'storefront-featured-article']) }}>
    <a href="{{ $url }}" class="storefront-featured-article__link">
        <div class="storefront-featured-article__media">
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="" class="storefront-featured-article__image" decoding="async">
            @else
                <div class="storefront-featured-article__placeholder"></div>
            @endif
        </div>

        <div class="storefront-featured-article__body">
            @if ($category)
                <span class="storefront-featured-article__category">{{ $category }}</span>
            @endif

            <h2 class="storefront-featured-article__title">{{ $post->title }}</h2>

            @if ($post->excerpt)
                <p class="storefront-featured-article__excerpt">{{ $post->excerpt }}</p>
            @endif

            <p class="storefront-featured-article__meta">
                @if ($author)
                    <span>{{ $author }}</span>
                @endif
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                        {{ $post->published_at->translatedFormat('M j, Y') }}
                    </time>
                @endif
                @if ($readingTime)
                    <span>{{ trans_choice('cms::blog.reading_time', $readingTime, ['minutes' => $readingTime]) }}</span>
                @endif
            </p>

            <span class="storefront-featured-article__cta">{{ __('cms::blog.read_article') }}</span>
        </div>
    </a>
</article>
