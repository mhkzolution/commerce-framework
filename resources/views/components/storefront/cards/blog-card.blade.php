@props([
    'post',
    'blogService',
    'featured' => false,
])

@php
    $url = route('storefront.cms.posts.show', $post->slug);
    $imageUrl = $blogService->featuredImageUrl($post, 'card');
    $imageSrcset = $blogService->featuredImageSrcset($post);
    $category = $blogService->categoryLabel($post);
    $author = $blogService->authorName($post);
@endphp

<article {{ $attributes->merge(['class' => 'storefront-article-card']) }}>
    <a href="{{ $url }}" class="storefront-article-card__link">
        <span class="storefront-article-card__media">
            @if ($imageUrl)
                <x-storefront.media.img
                    :src="$imageUrl"
                    :srcset="$imageSrcset"
                    :sizes="config('media.sizes.blog')"
                    alt=""
                    class="storefront-article-card__image"
                />
            @else
                <span class="storefront-article-card__placeholder"></span>
            @endif
        </span>

        <span class="storefront-article-card__body">
            @if ($category)
                <span class="storefront-article-card__category">{{ $category }}</span>
            @endif

            <span class="storefront-article-card__title">{{ $post->title }}</span>

            @if ($post->excerpt)
                <span class="storefront-article-card__excerpt">{{ $post->excerpt }}</span>
            @endif

            <span class="storefront-article-card__meta">
                @if ($author)
                    <span>{{ $author }}</span>
                @endif
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toIso8601String() }}">
                        {{ $post->published_at->translatedFormat('M j, Y') }}
                    </time>
                @endif
            </span>
        </span>
    </a>
</article>
