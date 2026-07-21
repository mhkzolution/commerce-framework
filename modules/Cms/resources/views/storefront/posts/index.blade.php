@extends('cart::layouts.storefront')

@section('title', 'Blog')

@section('content')
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-text">Blog</h1>
        <p class="mt-2 text-sm text-muted">Latest news and updates.</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        @forelse ($posts as $post)
            <article class="rounded-lg border border-border bg-surface p-6 shadow-sm">
                <h2 class="text-xl font-medium text-text">
                    <a href="{{ route('storefront.cms.posts.show', $post->slug) }}" class="hover:underline">{{ $post->title }}</a>
                </h2>
                @if ($post->published_at)
                    <p class="mt-1 text-xs text-muted">{{ $post->published_at->format('M j, Y') }}</p>
                @endif
                @if ($post->excerpt)
                    <p class="mt-3 text-sm text-text-secondary">{{ $post->excerpt }}</p>
                @endif
            </article>
        @empty
            <p class="text-muted">No posts published yet.</p>
        @endforelse
    </div>

    @if ($posts->hasPages())
        <div class="mt-8">{{ $posts->links() }}</div>
    @endif
@endsection
