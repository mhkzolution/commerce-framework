@props([
    'uuid' => null,
    'src' => null,
    'srcset' => null,
    'sizes' => null,
    'variant' => 'card',
    'alt' => '',
    'width' => null,
    'height' => null,
    'loading' => 'lazy',
    'fetchpriority' => null,
])

@php
    $src = is_string($src) && $src !== '' ? $src : null;
    $srcset = is_string($srcset) && $srcset !== '' ? $srcset : null;
    $uuid = is_string($uuid) && $uuid !== '' ? $uuid : null;

    if ($uuid !== null && app()->bound(\Commerce\Contracts\Media\MediaQueryServiceInterface::class)) {
        $media = app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class);
        $src ??= $media->getUrl($uuid, $variant) ?? $media->getUrl($uuid);
        $srcset ??= $media->getSrcset($uuid);
    }

    $sizes = is_string($sizes) && $sizes !== ''
        ? $sizes
        : (is_string($variant) ? config('media.sizes.'.$variant) : null);
@endphp

@if ($src)
    <img
        src="{{ $src }}"
        @if ($srcset) srcset="{{ $srcset }}" @endif
        @if ($sizes) sizes="{{ $sizes }}" @endif
        alt="{{ $alt }}"
        @if ($width) width="{{ $width }}" @endif
        @if ($height) height="{{ $height }}" @endif
        @if ($loading) loading="{{ $loading }}" @endif
        @if ($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
        decoding="async"
        {{ $attributes }}
    >
@endif
