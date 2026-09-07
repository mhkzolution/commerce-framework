@props(['url' => null])

@if (is_string($url) && $url !== '')
    <link rel="icon" href="{{ $url }}">
    <link rel="apple-touch-icon" href="{{ $url }}">
@endif
