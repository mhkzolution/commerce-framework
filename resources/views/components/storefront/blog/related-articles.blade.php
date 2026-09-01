@props([
    'posts' => [],
    'blogService',
])

@if ($posts->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'storefront-related-articles']) }}>
        <h2 class="storefront-related-articles__title">{{ __('cms::blog.related_articles') }}</h2>
        <x-storefront.blog.article-grid>
            @foreach ($posts as $related)
                <x-storefront.blog.article-card :post="$related" :blog-service="$blogService" />
            @endforeach
        </x-storefront.blog.article-grid>
    </section>
@endif
