@extends('cart::layouts.storefront')

@section('title', $post->title)

@section('content')
    <article>
        <a href="{{ route('storefront.cms.posts.index') }}" class="text-sm text-muted hover:underline">← Back to blog</a>
        <h1 class="mt-4 text-3xl font-semibold text-text">{{ $post->title }}</h1>
        @if ($post->published_at)
            <p class="mt-2 text-sm text-muted">{{ $post->published_at->format('F j, Y') }}</p>
        @endif
        @if ($post->excerpt)
            <p class="mt-4 text-lg text-text-secondary">{{ $post->excerpt }}</p>
        @endif
        <div class="mt-6 whitespace-pre-wrap text-text-secondary">{{ $post->content }}</div>
    </article>
@endsection
