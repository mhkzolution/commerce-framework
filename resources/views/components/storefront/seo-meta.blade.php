@props([
    'meta' => null,
])

@if ($meta)
    @if (! empty($meta['robots']))
        <meta name="robots" content="{{ $meta['robots'] }}">
    @endif
    @if (! empty($meta['description']))
        <meta name="description" content="{{ $meta['description'] }}">
    @endif
    @if (! empty($meta['keywords']))
        <meta name="keywords" content="{{ $meta['keywords'] }}">
    @endif
    @if (! empty($meta['canonical']))
        <link rel="canonical" href="{{ $meta['canonical'] }}">
    @endif
    <meta property="og:title" content="{{ $meta['title'] }}">
    @if (! empty($meta['description']))
        <meta property="og:description" content="{{ $meta['description'] }}">
    @endif
    @if (! empty($meta['ogImage']))
        <meta property="og:image" content="{{ $meta['ogImage'] }}">
    @endif
@endif
