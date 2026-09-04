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
    @if (! empty($meta['title']))
        <meta property="og:title" content="{{ $meta['title'] }}">
    @endif
    @if (! empty($meta['description']))
        <meta property="og:description" content="{{ $meta['description'] }}">
    @endif
    @if (! empty($meta['ogImage']))
        <meta property="og:image" content="{{ $meta['ogImage'] }}">
    @endif
    @if (! empty($meta['url'] ?? $meta['canonical'] ?? null))
        <meta property="og:url" content="{{ $meta['url'] ?? $meta['canonical'] }}">
    @endif
    @if (! empty($meta['ogType']))
        <meta property="og:type" content="{{ $meta['ogType'] }}">
    @endif
    <meta name="twitter:card" content="{{ $meta['twitterCard'] ?? 'summary_large_image' }}">
    @if (! empty($meta['title']))
        <meta name="twitter:title" content="{{ $meta['title'] }}">
    @endif
    @if (! empty($meta['description']))
        <meta name="twitter:description" content="{{ $meta['description'] }}">
    @endif
    @if (! empty($meta['ogImage']))
        <meta name="twitter:image" content="{{ $meta['ogImage'] }}">
    @endif
@endif
