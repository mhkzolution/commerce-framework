@props([
    'category' => null,
    'categoryUrl' => null,
    'author' => null,
    'authorUrl' => null,
    'publishedAt' => null,
    'readingTime' => null,
])

<div {{ $attributes->merge(['class' => 'storefront-article-meta']) }}>
    @if ($category)
        @if ($categoryUrl)
            <a href="{{ $categoryUrl }}" class="storefront-article-meta__category">{{ $category }}</a>
        @else
            <span class="storefront-article-meta__category">{{ $category }}</span>
        @endif
    @endif
    @if ($author)
        @if ($authorUrl)
            <a href="{{ $authorUrl }}" class="storefront-article-meta__author">{{ $author }}</a>
        @else
            <span class="storefront-article-meta__author">{{ $author }}</span>
        @endif
    @endif
    @if ($publishedAt)
        <time class="storefront-article-meta__date" datetime="{{ $publishedAt->toIso8601String() }}">
            {{ $publishedAt->translatedFormat('M j, Y') }}
        </time>
    @endif
    @if ($readingTime)
        <span class="storefront-article-meta__reading-time">
            {{ trans_choice('cms::blog.reading_time', $readingTime, ['minutes' => $readingTime]) }}
        </span>
    @endif
</div>
