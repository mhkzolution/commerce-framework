@extends('cart::layouts.storefront')

@section('title', $archiveTitle ?? __('cms::blog.title'))

@push('head')
    <x-storefront.json-ld :data="$structuredData ?? null" />
@endpush

@section('content')
    <div class="storefront-blog" data-blog>
        <x-storefront.blog.toolbar
            :filters="$filters"
            :categories="$categories"
            :archive-title="$archiveTitle ?? null"
        />

        <div class="storefront-blog__layout">
            <div class="storefront-blog__main" data-blog-results>
                @if ($featured)
                    <x-storefront.blog.featured-article :post="$featured" :blog-service="$blogService" />
                @endif

                <x-storefront.blog.article-grid>
                    @forelse ($posts as $post)
                        <x-storefront.blog.article-card :post="$post" :blog-service="$blogService" />
                    @empty
                        <x-storefront.empty-state :title="__('cms::blog.no_posts')" class="storefront-blog__empty" />
                    @endforelse
                </x-storefront.blog.article-grid>

                @if ($posts->hasPages())
                    <div class="storefront-blog__pagination">{{ $posts->withQueryString()->links() }}</div>
                @endif
            </div>

            <x-storefront.blog.sidebar
                :recent-posts="$recentPosts"
                :popular-tags="$popularTags"
                :filters="$filters"
                :blog-service="$blogService"
            />
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/storefront/blog.js')
@endpush
