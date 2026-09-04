@extends('cart::layouts.storefront')

@section('title', $archiveTitle ?? __('cms::blog.title'))
@section('main_class', 'storefront-blog-main')

@push('head')
    <x-storefront.json-ld :data="$structuredData ?? null" />
@endpush

@section('content')
    <x-storefront.layout.page-container class="storefront-blog" data-blog>
        <x-storefront.blog.toolbar
            :filters="$filters"
            :categories="$categories"
            :archive-title="$archiveTitle ?? null"
        />

        <div class="storefront-blog__main" data-blog-results>
            @if ($featured)
                <x-storefront.blog.featured-article :post="$featured" :blog-service="$blogService" />
            @endif

            <x-storefront.blog.article-grid>
                @forelse ($posts as $post)
                    <x-storefront.blog.article-card :post="$post" :blog-service="$blogService" />
                @empty
                    @if (! $featured)
                        <x-storefront.empty-state :title="__('cms::blog.no_posts')" class="storefront-blog__empty" />
                    @endif
                @endforelse
            </x-storefront.blog.article-grid>

            @if ($posts->hasPages())
                <div class="storefront-blog__pagination">{{ $posts->withQueryString()->links('pagination::storefront') }}</div>
            @endif
        </div>
    </x-storefront.layout.page-container>
@endsection
