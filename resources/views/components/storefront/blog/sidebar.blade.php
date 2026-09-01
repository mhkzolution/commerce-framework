@props([
    'recentPosts' => [],
    'popularTags' => [],
    'filters' => null,
    'blogService' => null,
])

<aside {{ $attributes->merge(['class' => 'storefront-blog-sidebar']) }} aria-label="{{ __('cms::blog.sidebar') }}">
    @if (config('cms.newsletter.enabled', true))
        <x-storefront.blog.newsletter class="storefront-blog-sidebar__block" />
    @endif

    @if ($recentPosts !== [])
        <section class="storefront-blog-sidebar__block">
            <h2 class="storefront-blog-sidebar__title">{{ __('cms::blog.recent_posts') }}</h2>
            <ul class="storefront-blog-sidebar__list">
                @foreach ($recentPosts as $recent)
                    <li>
                        <a href="{{ route('storefront.cms.posts.show', $recent->slug) }}" class="storefront-blog-sidebar__link">
                            {{ $recent->title }}
                        </a>
                        @if ($recent->published_at)
                            <time class="storefront-blog-sidebar__date" datetime="{{ $recent->published_at->toIso8601String() }}">
                                {{ $recent->published_at->translatedFormat('M j, Y') }}
                            </time>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($popularTags !== [])
        <section class="storefront-blog-sidebar__block">
            <h2 class="storefront-blog-sidebar__title">{{ __('cms::blog.popular_tags') }}</h2>
            <div class="storefront-blog-sidebar__tags">
                @foreach ($popularTags as $tagRow)
                    @php
                        $slug = $tagRow['slug'] ?? $tagRow['tag'] ?? null;
                        $name = $tagRow['name'] ?? $tagRow['tag'] ?? $slug;
                    @endphp
                    @continue(! $slug)
                    <a
                        href="{{ route('storefront.cms.tags.show', $slug) }}"
                        class="storefront-blog-sidebar__tag {{ ($filters?->tag ?? null) === $slug ? 'storefront-blog-sidebar__tag--active' : '' }}"
                    >
                        {{ $name }}
                        <span class="storefront-blog-sidebar__tag-count">{{ $tagRow['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</aside>
