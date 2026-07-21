@extends('cart::layouts.storefront')

@section('title', $page->title)

@section('content')
    <article class="prose max-w-none">
        <h1 class="text-3xl font-semibold text-text">{{ $page->title }}</h1>
        <div class="mt-6 text-text-secondary whitespace-pre-wrap">{{ $page->content }}</div>
    </article>
@endsection
